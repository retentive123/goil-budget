Attribute VB_Name = "ReconModule"
'=====================================================================
'  APEX BANK  <->  KINTAMPO RURAL BANK  ACCOUNT RECONCILIATION
'  Sheets used : Dashboard, External (Apex statement),
'                Internal (our ledger), Recon Report, Matched
'  Main macro  : ReconcileAccounts
'  Helpers     : ImportExternal, ImportInternal
'
'  Matching logic
'   0. Self-reversals inside the same statement (same amount, opposite
'      sign, same ref or near-identical narration, within 3 days)
'   1. Exact amount, opposite side, within <Tight days>  -> best narration
'   2. Exact amount, opposite side, within <Wide days>   -> best narration
'   3. Opposite side, similar narration, amount differs  -> "Amount difference"
'   Everything left is an exception.
'  Narrations are normalised (dates, abbreviations, filler words) and
'  compared with a character-bigram (Dice) similarity score 0..1.
'=====================================================================
Option Explicit

Private Type Txn
    RowNo As Long
    TDate As Date
    TType As String
    Ref As String
    Narr As String
    Norm As String
    Dr As Double
    Cr As Double
    Amt As Double          ' Credit - Debit
    Status As String       ' "", "Matched", "Reversal", "AmtDiff"
End Type

Private Const NAVY As Long = 6567712     ' RGB(32,55,100)
Private Const LIGHT As Long = 15652797   ' RGB(221,235,247)
Private Const AMBER As Long = 10284031   ' RGB(255,235,156)
Private Const PINK As Long = 13551615    ' RGB(255,199,206)
Private Const GREEN As Long = 13561798   ' RGB(198,239,206)

'---------------------------------------------------------------------
Public Sub ImportExternal()
    ImportCsvTo "External"
End Sub

Public Sub ImportInternal()
    ImportCsvTo "Internal"
End Sub

Private Sub ImportCsvTo(sheetName As String)
    Dim f As Variant, wb As Workbook, ws As Worksheet
    f = Application.GetOpenFilename("Statement files (*.csv;*.xlsx;*.xls),*.csv;*.xlsx;*.xls", , "Select statement for " & sheetName)
    If VarType(f) = vbBoolean Then Exit Sub
    Application.ScreenUpdating = False
    Set ws = ThisWorkbook.Worksheets(sheetName)
    ws.Cells.Clear
    Set wb = Workbooks.Open(CStr(f), ReadOnly:=True, Local:=True)
    wb.Worksheets(1).UsedRange.Copy
    ws.Range("A1").PasteSpecial xlPasteValuesAndNumberFormats
    Application.CutCopyMode = False
    wb.Close SaveChanges:=False
    ws.Columns.AutoFit
    ThisWorkbook.Worksheets("Dashboard").Activate
    Application.ScreenUpdating = True
    MsgBox sheetName & " statement imported from:" & vbCrLf & f, vbInformation
End Sub

'---------------------------------------------------------------------
Public Sub ReconcileAccounts()
    Dim ext() As Txn, itn() As Txn
    Dim nE As Long, nI As Long
    Dim eOpen As Double, eClose As Double, iOpen As Double, iClose As Double
    Dim tightDays As Long, wideDays As Long, simThr As Double, pctThr As Double
    Dim dash As Worksheet

    On Error GoTo Fail
    Set dash = ThisWorkbook.Worksheets("Dashboard")
    tightDays = SettingOr(dash, "TightDays", 3)
    wideDays = SettingOr(dash, "WideDays", 15)
    simThr = SettingOr(dash, "SimThreshold", 0.7)
    pctThr = SettingOr(dash, "AmtTolerance", 0.05)

    Application.ScreenUpdating = False
    Application.Calculation = xlCalculationManual

    nE = LoadStatement(ThisWorkbook.Worksheets("External"), ext, eOpen, eClose)
    nI = LoadStatement(ThisWorkbook.Worksheets("Internal"), itn, iOpen, iClose)
    If nE = 0 Or nI = 0 Then
        MsgBox "No transactions found. Paste/import both statements first " & _
               "(External = Apex statement, Internal = our ledger).", vbExclamation
        GoTo Done
    End If

    ' match arrays: (k,1)=ext index, (k,2)=int index, (k,3)=type code
    Dim mExt() As Long, mI() As Long, mType() As String, nM As Long
    ReDim mExt(1 To nE + nI): ReDim mI(1 To nE + nI): ReDim mType(1 To nE + nI)

    ' ---- pass 0: self-reversals inside each statement
    FindReversals ext, nE
    FindReversals itn, nI

    ' ---- pass 1 & 2: exact amount, opposite side
    Dim pass As Long, maxDays As Long, i As Long, j As Long
    Dim best As Long, bestSc As Double, sc As Double, dd As Long
    For pass = 1 To 2
        If pass = 1 Then maxDays = tightDays Else maxDays = wideDays
        For i = 1 To nE
            If ext(i).Status = "" Then
                best = 0: bestSc = -999
                For j = 1 To nI
                    If itn(j).Status = "" Then
                        If Abs(ext(i).Amt + itn(j).Amt) < 0.005 Then
                            dd = Abs(ext(i).TDate - itn(j).TDate)
                            If dd <= maxDays Then
                                sc = Sim(ext(i).Norm, itn(j).Norm) - dd * 0.02
                                If sc > bestSc Then bestSc = sc: best = j
                            End If
                        End If
                    End If
                Next j
                If best > 0 Then
                    ext(i).Status = "Matched": itn(best).Status = "Matched"
                    nM = nM + 1: mExt(nM) = i: mI(nM) = best
                    If pass = 1 Then mType(nM) = "Exact" Else mType(nM) = "Exact - date gap"
                End If
            End If
        Next i
    Next pass

    ' ---- pass 3: same item, different amount
    Dim dE() As Long, dI() As Long, dS() As Double, nD As Long, s As Double, big As Double
    ReDim dE(1 To nE): ReDim dI(1 To nE): ReDim dS(1 To nE)
    For i = 1 To nE
        If ext(i).Status = "" Then
            best = 0: bestSc = -1
            For j = 1 To nI
                If itn(j).Status = "" Then
                    If (ext(i).Amt > 0) <> (itn(j).Amt > 0) Then
                        If Abs(ext(i).TDate - itn(j).TDate) <= 7 Then
                            s = Sim(ext(i).Norm, itn(j).Norm)
                            big = Application.WorksheetFunction.Max(Abs(ext(i).Amt), Abs(itn(j).Amt))
                            If s >= simThr Or (s >= 0.5 And Abs(Abs(ext(i).Amt) - Abs(itn(j).Amt)) <= pctThr * big) Then
                                If s > bestSc Then bestSc = s: best = j
                            End If
                        End If
                    End If
                End If
            Next j
            If best > 0 Then
                ext(i).Status = "AmtDiff": itn(best).Status = "AmtDiff"
                nD = nD + 1: dE(nD) = i: dI(nD) = best: dS(nD) = bestSc
            End If
        End If
    Next i

    WriteMatched ext, itn, mExt, mI, mType, nM
    WriteReport ext, nE, itn, nI, dE, dI, dS, nD, eOpen, eClose, iOpen, iClose, nM
    WriteDashboardSummary dash, nE, nI, nM, nD, ext, itn, eClose, iClose

    ThisWorkbook.Worksheets("Recon Report").Activate
    ThisWorkbook.Worksheets("Recon Report").Range("A1").Select
    ActiveWindow.DisplayGridlines = False
Done:
    Application.Calculation = xlCalculationAutomatic
    Application.ScreenUpdating = True
    Exit Sub
Fail:
    Application.Calculation = xlCalculationAutomatic
    Application.ScreenUpdating = True
    MsgBox "Reconciliation stopped: " & Err.Description, vbCritical
End Sub

'---------------------------------------------------------------------
'  Reading the statements
'---------------------------------------------------------------------
Private Function LoadStatement(ws As Worksheet, ByRef t() As Txn, ByRef openBal As Double, ByRef closeBal As Double) As Long
    Dim lastRow As Long, r As Long, n As Long, a As String, b As String, d As Date
    lastRow = Application.WorksheetFunction.Max(ws.Cells(ws.Rows.Count, 1).End(xlUp).Row, _
                                               ws.Cells(ws.Rows.Count, 2).End(xlUp).Row)
    ReDim t(1 To lastRow + 1)
    For r = 1 To lastRow
        a = UCase(Clean(ws.Cells(r, 1).Text))
        b = UCase(Clean(ws.Cells(r, 2).Text))
        If a Like "BALANCE AT PERIOD START*" Then
            openBal = ToNum(ws.Cells(r, 2).Value)
        ElseIf b Like "BALANCE AT PERIOD END*" Then
            closeBal = ToNum(ws.Cells(r, 10).Value)
        ElseIf GetDate(ws.Cells(r, 1).Value, d) Then
            n = n + 1
            With t(n)
                .RowNo = r
                .TDate = d
                .TType = Clean(ws.Cells(r, 2).Text)
                .Ref = Clean(ws.Cells(r, 3).Text)
                .Dr = Abs(ToNum(ws.Cells(r, 7).Value))
                .Cr = Abs(ToNum(ws.Cells(r, 8).Value))
                .Amt = Round(.Cr - .Dr, 2)
                .Narr = ""
                .Status = ""
            End With
        ElseIf n > 0 And a = "" And b <> "" Then
            t(n).Narr = Trim(t(n).Narr & " " & Clean(ws.Cells(r, 2).Text))
        End If
    Next r
    Dim k As Long
    For k = 1 To n
        Select Case t(k).TType
            Case "New MM Deposit", "Repayment of Principal", "Payment of Interest", "Redemption Sale", "Securities Purchase"
                t(k).Norm = NormNarr(t(k).TType & " " & t(k).Narr)
            Case Else
                If t(k).Narr = "" Then t(k).Norm = NormNarr(t(k).TType) Else t(k).Norm = NormNarr(t(k).Narr)
        End Select
    Next k
    LoadStatement = n
End Function

Private Function GetDate(v As Variant, ByRef d As Date) As Boolean
    If VarType(v) = vbDate Then d = v: GetDate = True: Exit Function
    Dim s As String: s = Clean(CStr(v))
    If s Like "#-???-##" Or s Like "##-???-##" Or s Like "#-???-####" Or s Like "##-???-####" Then
        On Error Resume Next
        d = CDate(s)
        GetDate = (Err.Number = 0)
        Err.Clear
        On Error GoTo 0
    End If
End Function

Private Function ToNum(v As Variant) As Double
    If VarType(v) = vbDouble Or VarType(v) = vbCurrency Or VarType(v) = vbLong Or VarType(v) = vbInteger Then
        ToNum = CDbl(v): Exit Function
    End If
    Dim s As String: s = Clean(CStr(v))
    s = Replace(s, "'", ""): s = Replace(s, ",", ""): s = Replace(s, " ", "")
    If s = "" Or s = "-" Then Exit Function
    If Left$(s, 1) = "(" And Right$(s, 1) = ")" Then s = "-" & Mid$(s, 2, Len(s) - 2)
    ToNum = Val(s)
End Function

Private Function Clean(ByVal s As String) As String
    Clean = Trim$(Replace(s, Chr(160), " "))
End Function

'---------------------------------------------------------------------
'  Narration handling
'---------------------------------------------------------------------
Private Function NormNarr(ByVal s As String) As String
    Dim re As Object, w As Variant, outS As String, mapped As String
    Set re = CreateObject("VBScript.RegExp")
    re.Global = True
    s = UCase(Clean(s))
    re.Pattern = "\b(\d{2})-(\d{2})-(\d{4})\b": s = re.Replace(s, "$3$2$1")   ' 30-09-2026
    re.Pattern = "\b(\d{4})-(\d{2})-(\d{2})\b": s = re.Replace(s, "$1$2$3")   ' 2026-09-30
    re.Pattern = "\b(\d{2})-(\d{2})-(\d{2})\b": s = re.Replace(s, "20$3$2$1") ' 30-09-26
    re.Pattern = "\b([0-3]\d)([01]\d)(20\d\d)\b": s = re.Replace(s, "$3$2$1") ' 30092026
    re.Pattern = "[^A-Z0-9\- ]": s = re.Replace(s, " ")

    mapped = ""
    For Each w In Split(s, " ")
        If w <> "" Then mapped = mapped & " " & Synonym(CStr(w))
    Next w
    outS = ""
    For Each w In Split(mapped, " ")
        If w <> "" Then
            If InStr(1, "|KINTAMPO|KINTAPO|RURAL|BANK|LIMITED|RBL|FOR|THE|OF|IFO|PROCESS|PRS|PRSNT|P|PR|TO|APEX|ON|DD|SET|SETT|JEMA|", "|" & w & "|") = 0 Then
                outS = outS & " " & w
            End If
        End If
    Next w
    NormNarr = Trim$(outS)
End Function

Private Function Synonym(w As String) As String
    Select Case w
        Case "BANK2WALLET": Synonym = "BNK2WLT"
        Case "MOBILEBANK2WALLET": Synonym = "MOBILE BNK2WLT"
        Case "SEPTEMBER", "SEPT": Synonym = "SEP"
        Case "AUGUST": Synonym = "AUG"
        Case "AUGUST2026": Synonym = "AUG 2026"
        Case "FAVENET": Synonym = "FAVNET"
        Case "UNFAVNETCLGSETT": Synonym = "UNFAVNET CLG"
        Case "FAVNETCLGEXPSETT": Synonym = "FAVNET CLGEXP"
        Case "RECEIVED": Synonym = "TRFD"
        Case "E-VALUE", "CASH": Synonym = "ECASH"
        Case "E": Synonym = ""
        Case "TBILLS", "BILL": Synonym = "TBILL"
        Case "BNKS": Synonym = "BANKERS"
        Case "CHEQUES", "CHQ": Synonym = "CHQS"
        Case "PRSTD", "PRNSTD", "PRSNTD": Synonym = "PRESENTED"
        Case "RTDS": Synonym = "RTD"
        Case Else: Synonym = w
    End Select
End Function

' Dice coefficient on character bigrams (spaces removed): 0 = different, 1 = identical
Private Function Sim(ByVal a As String, ByVal b As String) As Double
    Dim d As Object, i As Long, g As String, hit As Long
    a = Replace(a, " ", ""): b = Replace(b, " ", "")
    If Len(a) < 2 Or Len(b) < 2 Then Exit Function
    Set d = CreateObject("Scripting.Dictionary")
    For i = 1 To Len(a) - 1
        g = Mid$(a, i, 2)
        d(g) = d(g) + 1
    Next i
    For i = 1 To Len(b) - 1
        g = Mid$(b, i, 2)
        If d.Exists(g) Then
            If d(g) > 0 Then
                d(g) = d(g) - 1
                hit = hit + 1
            End If
        End If
    Next i
    Sim = 2 * hit / ((Len(a) - 1) + (Len(b) - 1))
End Function

Private Sub FindReversals(ByRef t() As Txn, n As Long)
    Dim i As Long, j As Long
    For i = 1 To n - 1
        If t(i).Status = "" Then
            For j = i + 1 To n
                If t(j).Status = "" Then
                    If Abs(t(i).Amt + t(j).Amt) < 0.005 And Abs(t(i).TDate - t(j).TDate) <= 3 Then
                        If t(i).Ref = t(j).Ref Or Sim(t(i).Norm, t(j).Norm) >= 0.85 Then
                            t(i).Status = "Reversal": t(j).Status = "Reversal"
                            Exit For
                        End If
                    End If
                End If
            Next j
        End If
    Next i
End Sub

Private Function SettingOr(ws As Worksheet, nm As String, dflt As Double) As Double
    On Error GoTo UseDefault
    Dim v As Variant: v = ws.Range(nm).Value
    If IsNumeric(v) And Not IsEmpty(v) Then SettingOr = CDbl(v) Else SettingOr = dflt
    Exit Function
UseDefault:
    SettingOr = dflt
End Function

'---------------------------------------------------------------------
'  Output
'---------------------------------------------------------------------
Private Function GetSheet(nm As String) As Worksheet
    On Error Resume Next
    Set GetSheet = ThisWorkbook.Worksheets(nm)
    On Error GoTo 0
    If GetSheet Is Nothing Then
        Set GetSheet = ThisWorkbook.Worksheets.Add(After:=ThisWorkbook.Worksheets(ThisWorkbook.Worksheets.Count))
        GetSheet.Name = nm
    End If
End Function

Private Sub HeaderRow(ws As Worksheet, r As Long, heads As Variant)
    Dim k As Long
    For k = 0 To UBound(heads)
        ws.Cells(r, k + 1).Value = heads(k)
    Next k
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, UBound(heads) + 1))
        .Font.Bold = True
        .Font.Color = vbWhite
        .Interior.Color = NAVY
        .WrapText = True
        .VerticalAlignment = xlCenter
    End With
End Sub

Private Sub SectionTitle(ws As Worksheet, r As Long, txt As String, cnt As Long, amt As Double, clr As Long)
    ws.Cells(r, 1).Value = txt & "  (" & cnt & " items, net " & Format(amt, "#,##0.00") & ")"
    With ws.Range(ws.Cells(r, 1), ws.Cells(r, 8))
        .Interior.Color = clr
        .Font.Bold = True
        .Font.Size = 12
    End With
End Sub

Private Sub WriteMatched(ext() As Txn, itn() As Txn, mExt() As Long, mI() As Long, mType() As String, nM As Long)
    Dim ws As Worksheet, r As Long, k As Long, s As Double
    Set ws = GetSheet("Matched")
    ws.Cells.Clear
    ws.Range("A1").Value = "MATCHED ITEMS"
    ws.Range("A1").Font.Bold = True: ws.Range("A1").Font.Size = 14
    ws.Range("A2").Value = nM & " matched pairs. Rows flagged 'Review' matched on amount but narrations differ a lot - eyeball them."
    HeaderRow ws, 4, Array("#", "Match type", "Apex date", "Apex ref", "Apex narration", "Apex amount", _
                           "Ledger date", "Ledger ref", "Ledger narration", "Ledger amount", "Day gap", "Narration score", "Flag")
    r = 5
    For k = 1 To nM
        s = Sim(ext(mExt(k)).Norm, itn(mI(k)).Norm)
        ws.Cells(r, 1).Value = k
        ws.Cells(r, 2).Value = mType(k)
        ws.Cells(r, 3).Value = ext(mExt(k)).TDate
        ws.Cells(r, 4).Value = ext(mExt(k)).Ref
        ws.Cells(r, 5).Value = NarrText(ext(mExt(k)))
        ws.Cells(r, 6).Value = ext(mExt(k)).Amt
        ws.Cells(r, 7).Value = itn(mI(k)).TDate
        ws.Cells(r, 8).Value = itn(mI(k)).Ref
        ws.Cells(r, 9).Value = NarrText(itn(mI(k)))
        ws.Cells(r, 10).Value = itn(mI(k)).Amt
        ws.Cells(r, 11).Value = Abs(ext(mExt(k)).TDate - itn(mI(k)).TDate)
        ws.Cells(r, 12).Value = Round(s, 2)
        If s < 0.5 Then
            ws.Cells(r, 13).Value = "Review"
            ws.Range(ws.Cells(r, 1), ws.Cells(r, 13)).Interior.Color = AMBER
        Else
            ws.Cells(r, 13).Value = "OK"
        End If
        r = r + 1
    Next k
    FormatTable ws, 4, r - 1, 13, Array(3, 7), Array(6, 10)
    ws.Columns(5).ColumnWidth = 45: ws.Columns(9).ColumnWidth = 45
End Sub

Private Function NarrText(t As Txn) As String
    If t.Narr = "" Then NarrText = t.TType Else NarrText = t.TType & " - " & t.Narr
End Function

Private Sub FormatTable(ws As Worksheet, hdr As Long, lastR As Long, nCols As Long, dateCols As Variant, numCols As Variant)
    Dim c As Variant
    If lastR > hdr Then
        For Each c In dateCols
            ws.Range(ws.Cells(hdr + 1, c), ws.Cells(lastR, c)).NumberFormat = "dd-mmm-yy"
        Next c
        For Each c In numCols
            ws.Range(ws.Cells(hdr + 1, c), ws.Cells(lastR, c)).NumberFormat = "#,##0.00;[Red](#,##0.00)"
        Next c
    End If
    With ws.Range(ws.Cells(hdr, 1), ws.Cells(Application.WorksheetFunction.Max(lastR, hdr), nCols)).Borders
        .LineStyle = xlContinuous
        .Color = RGB(191, 191, 191)
    End With
    ws.Range(ws.Cells(hdr, 1), ws.Cells(hdr, nCols)).EntireColumn.AutoFit
End Sub

Private Function ExcAction(t As Txn, isExt As Boolean) As String
    Dim n As String: n = UCase(t.TType & " " & t.Narr)
    If isExt Then
        If t.Amt > 0 Then ExcAction = "Apex credited our account - not in ledger. Confirm and post." _
                     Else ExcAction = "Apex debited our account - not in ledger. Confirm and post."
    Else
        If t.Amt < 0 Then ExcAction = "Ledger shows money into Apex - not on Apex statement. Follow up with Apex." _
                     Else ExcAction = "Ledger shows money out of Apex - not on Apex statement. Follow up with Apex."
    End If
    If InStr(n, "ITC") > 0 Or InStr(n, "INTEREST") > 0 Or InStr(n, "MICR") > 0 Or InStr(n, "COMM") > 0 Then
        ExcAction = ExcAction & " (charge / interest item)"
    ElseIf InStr(n, "TBILL") > 0 Or InStr(n, "REDEMPTION") > 0 Or InStr(n, "SECURIT") > 0 Then
        ExcAction = ExcAction & " (T-bill item - check investment schedule)"
    End If
End Function

Private Sub WriteExceptionList(ws As Worksheet, ByRef r As Long, t() As Txn, n As Long, isExt As Boolean, title As String, clr As Long)
    Dim k As Long, cnt As Long, tot As Double, startR As Long
    For k = 1 To n
        If t(k).Status = "" Then cnt = cnt + 1: tot = tot + t(k).Amt
    Next k
    SectionTitle ws, r, title, cnt, tot, clr
    r = r + 1
    HeaderRow ws, r, Array("Date", "Type", "Reference", "Narration", "Debit", "Credit", "Age (days)", "Suggested action")
    startR = r
    r = r + 1
    If cnt = 0 Then
        ws.Cells(r, 1).Value = "None - fully reconciled."
        r = r + 1
    Else
        For k = 1 To n
            If t(k).Status = "" Then
                ws.Cells(r, 1).Value = t(k).TDate
                ws.Cells(r, 2).Value = t(k).TType
                ws.Cells(r, 3).Value = t(k).Ref
                ws.Cells(r, 4).Value = t(k).Narr
                If t(k).Dr <> 0 Then ws.Cells(r, 5).Value = t(k).Dr
                If t(k).Cr <> 0 Then ws.Cells(r, 6).Value = t(k).Cr
                ws.Cells(r, 7).Value = Date - t(k).TDate
                ws.Cells(r, 8).Value = ExcAction(t(k), isExt)
                r = r + 1
            End If
        Next k
    End If
    ws.Range(ws.Cells(startR + 1, 1), ws.Cells(r - 1, 1)).NumberFormat = "dd-mmm-yy"
    ws.Range(ws.Cells(startR + 1, 5), ws.Cells(r - 1, 6)).NumberFormat = "#,##0.00"
    With ws.Range(ws.Cells(startR, 1), ws.Cells(r - 1, 8)).Borders
        .LineStyle = xlContinuous: .Color = RGB(191, 191, 191)
    End With
    r = r + 1
End Sub

Private Sub WriteReport(ext() As Txn, nE As Long, itn() As Txn, nI As Long, _
                        dE() As Long, dI() As Long, dS() As Double, nD As Long, _
                        eOpen As Double, eClose As Double, iOpen As Double, iClose As Double, nM As Long)
    Dim ws As Worksheet, r As Long, k As Long
    Dim sumUE As Double, sumUI As Double, sumD As Double, cUE As Long, cUI As Long, cRevE As Long, cRevI As Long
    Set ws = GetSheet("Recon Report")
    ws.Cells.Clear
    ws.Cells.Font.Name = "Calibri": ws.Cells.Font.Size = 10

    For k = 1 To nE
        If ext(k).Status = "" Then sumUE = sumUE + ext(k).Amt: cUE = cUE + 1
        If ext(k).Status = "Reversal" Then cRevE = cRevE + 1
    Next k
    For k = 1 To nI
        If itn(k).Status = "" Then sumUI = sumUI + itn(k).Amt: cUI = cUI + 1
        If itn(k).Status = "Reversal" Then cRevI = cRevI + 1
    Next k
    For k = 1 To nD
        sumD = sumD + ext(dE(k)).Amt + itn(dI(k)).Amt
    Next k

    ' ---------- title
    ws.Range("A1").Value = "APEX BANK ACCOUNT RECONCILIATION - EXCEPTIONS REPORT"
    ws.Range("A1").Font.Size = 16: ws.Range("A1").Font.Bold = True: ws.Range("A1").Font.Color = NAVY
    ws.Range("A2").Value = "Run on " & Format(Now, "dd-mmm-yyyy hh:nn") & "   |   Apex statement vs our ledger (Apex Bank a/c)"
    ws.Range("A2").Font.Italic = True

    ' ---------- reconciliation statement
    r = 4
    ws.Cells(r, 1).Value = "RECONCILIATION STATEMENT": ws.Cells(r, 1).Font.Bold = True: ws.Cells(r, 1).Font.Size = 12
    r = r + 1
    HeaderRow ws, r, Array("", "", "", "Amount (GHS)")
    Dim lines(1 To 9, 1 To 2) As Variant
    Dim bLedger As Double: bLedger = -iClose     ' ledger shows debits as negative -> debit balance = asset
    lines(1, 1) = "Closing balance per Apex statement": lines(1, 2) = eClose
    lines(2, 1) = "Closing balance per our ledger (debit = asset)": lines(2, 2) = bLedger
    lines(3, 1) = "DIFFERENCE TO EXPLAIN": lines(3, 2) = eClose - bLedger
    lines(4, 1) = "  Opening difference brought forward (unreconciled from prior period)": lines(4, 2) = eOpen + iOpen
    lines(5, 1) = "  Items on Apex statement not in ledger (" & cUE & ")": lines(5, 2) = sumUE
    lines(6, 1) = "  Items in ledger not on Apex statement (" & cUI & ")": lines(6, 2) = sumUI
    lines(7, 1) = "  Amount differences on matched items (" & nD & ")": lines(7, 2) = sumD
    lines(8, 1) = "TOTAL EXPLAINED": lines(8, 2) = lines(4, 2) + lines(5, 2) + lines(6, 2) + lines(7, 2)
    lines(9, 1) = "UNEXPLAINED (should be 0.00)": lines(9, 2) = Round(lines(3, 2) - lines(8, 2), 2)
    For k = 1 To 9
        r = r + 1
        ws.Cells(r, 1).Value = lines(k, 1)
        ws.Cells(r, 4).Value = lines(k, 2)
        ws.Cells(r, 4).NumberFormat = "#,##0.00;[Red](#,##0.00)"
        If k = 3 Or k = 8 Or k = 9 Then ws.Range(ws.Cells(r, 1), ws.Cells(r, 4)).Font.Bold = True
        If k = 3 Or k = 8 Then ws.Range(ws.Cells(r, 1), ws.Cells(r, 4)).Interior.Color = LIGHT
        If k = 9 Then
            If Abs(lines(9, 2)) < 0.01 Then ws.Range(ws.Cells(r, 1), ws.Cells(r, 4)).Interior.Color = GREEN _
                                       Else ws.Range(ws.Cells(r, 1), ws.Cells(r, 4)).Interior.Color = PINK
        End If
    Next k
    ws.Range(ws.Cells(5, 1), ws.Cells(r, 4)).Borders(xlEdgeBottom).LineStyle = xlContinuous

    ' ---------- summary counts (to the right)
    Dim sr As Long: sr = 5
    HeaderRow2 ws, sr, 6, "MATCHING SUMMARY", "Apex stmt", "Ledger"
    SumLine ws, sr + 1, "Transactions read", nE, nI
    SumLine ws, sr + 2, "Matched (1-to-1)", nM, nM
    SumLine ws, sr + 3, "Self-reversing entries", cRevE, cRevI
    SumLine ws, sr + 4, "Amount differences", nD, nD
    SumLine ws, sr + 5, "Unmatched (exceptions)", cUE, cUI
    SumLine ws, sr + 6, "Match rate", Format((nM + nD + cRevE) / nE, "0.0%"), Format((nM + nD + cRevI) / nI, "0.0%")
    ws.Range(ws.Cells(sr, 6), ws.Cells(sr + 6, 8)).Borders.LineStyle = xlContinuous

    ' ---------- amount differences
    r = r + 3
    SectionTitle ws, r, "1. AMOUNT DIFFERENCES - same transaction, different amount", nD, sumD, AMBER
    r = r + 1
    HeaderRow ws, r, Array("Apex date", "Apex narration", "Apex amount", "Ledger date", "Ledger narration", "Ledger amount", "Difference", "Narration score")
    Dim hdrR As Long: hdrR = r
    r = r + 1
    If nD = 0 Then ws.Cells(r, 1).Value = "None.": r = r + 1
    For k = 1 To nD
        ws.Cells(r, 1).Value = ext(dE(k)).TDate
        ws.Cells(r, 2).Value = NarrText(ext(dE(k)))
        ws.Cells(r, 3).Value = ext(dE(k)).Amt
        ws.Cells(r, 4).Value = itn(dI(k)).TDate
        ws.Cells(r, 5).Value = NarrText(itn(dI(k)))
        ws.Cells(r, 6).Value = itn(dI(k)).Amt
        ws.Cells(r, 7).Value = ext(dE(k)).Amt + itn(dI(k)).Amt
        ws.Cells(r, 8).Value = Round(dS(k), 2)
        r = r + 1
    Next k
    ws.Range(ws.Cells(hdrR + 1, 1), ws.Cells(r - 1, 1)).NumberFormat = "dd-mmm-yy"
    ws.Range(ws.Cells(hdrR + 1, 4), ws.Cells(r - 1, 4)).NumberFormat = "dd-mmm-yy"
    ws.Range(ws.Cells(hdrR + 1, 3), ws.Cells(r - 1, 3)).NumberFormat = "#,##0.00;[Red](#,##0.00)"
    ws.Range(ws.Cells(hdrR + 1, 6), ws.Cells(r - 1, 7)).NumberFormat = "#,##0.00;[Red](#,##0.00)"
    ws.Range(ws.Cells(hdrR, 1), ws.Cells(r - 1, 8)).Borders.LineStyle = xlContinuous
    r = r + 1

    ' ---------- unmatched lists
    WriteExceptionList ws, r, ext, nE, True, "2. ON APEX STATEMENT, NOT IN OUR LEDGER", PINK
    WriteExceptionList ws, r, itn, nI, False, "3. IN OUR LEDGER, NOT ON APEX STATEMENT", PINK

    ' ---------- reversals (info)
    SectionTitle ws, r, "4. SELF-REVERSING ENTRIES (info only - net to zero)", cRevE + cRevI, 0, LIGHT
    r = r + 1
    HeaderRow ws, r, Array("Side", "Date", "Reference", "Narration", "Debit", "Credit")
    hdrR = r: r = r + 1
    For k = 1 To nE
        If ext(k).Status = "Reversal" Then RevLine ws, r, "Apex", ext(k)
    Next k
    For k = 1 To nI
        If itn(k).Status = "Reversal" Then RevLine ws, r, "Ledger", itn(k)
    Next k
    ws.Range(ws.Cells(hdrR, 1), ws.Cells(r - 1, 6)).Borders.LineStyle = xlContinuous

    ' ---------- layout
    ws.Columns("A").ColumnWidth = 12
    ws.Columns("B").ColumnWidth = 34
    ws.Columns("C").ColumnWidth = 22
    ws.Columns("D").ColumnWidth = 40
    ws.Columns("E").ColumnWidth = 38
    ws.Columns("F").ColumnWidth = 16
    ws.Columns("G").ColumnWidth = 14
    ws.Columns("H").ColumnWidth = 62
    ws.Columns("B:E").WrapText = True
    ws.Columns("H").WrapText = True
    ws.Range("A1:A2").WrapText = False
    ws.Rows("4:14").WrapText = False
    With ws.PageSetup
        .Orientation = xlLandscape
        .Zoom = False
        .FitToPagesWide = 1
        .FitToPagesTall = False
    End With
End Sub

Private Sub HeaderRow2(ws As Worksheet, r As Long, c As Long, a As String, b As String, d As String)
    ws.Cells(r, c).Value = a: ws.Cells(r, c + 1).Value = b: ws.Cells(r, c + 2).Value = d
    With ws.Range(ws.Cells(r, c), ws.Cells(r, c + 2))
        .Font.Bold = True: .Font.Color = vbWhite: .Interior.Color = NAVY
    End With
End Sub

Private Sub SumLine(ws As Worksheet, r As Long, lbl As String, a As Variant, b As Variant)
    ws.Cells(r, 6).Value = lbl
    ws.Cells(r, 7).Value = a
    ws.Cells(r, 8).Value = b
    ws.Range(ws.Cells(r, 7), ws.Cells(r, 8)).HorizontalAlignment = xlRight
End Sub

Private Sub RevLine(ws As Worksheet, ByRef r As Long, side As String, t As Txn)
    ws.Cells(r, 1).Value = side
    ws.Cells(r, 2).Value = t.TDate: ws.Cells(r, 2).NumberFormat = "dd-mmm-yy"
    ws.Cells(r, 3).Value = t.Ref
    ws.Cells(r, 4).Value = NarrText(t)
    If t.Dr <> 0 Then ws.Cells(r, 5).Value = t.Dr
    If t.Cr <> 0 Then ws.Cells(r, 6).Value = t.Cr
    ws.Range(ws.Cells(r, 5), ws.Cells(r, 6)).NumberFormat = "#,##0.00"
    r = r + 1
End Sub

Private Sub WriteDashboardSummary(dash As Worksheet, nE As Long, nI As Long, nM As Long, nD As Long, _
                                  ext() As Txn, itn() As Txn, eClose As Double, iClose As Double)
    Dim k As Long, cUE As Long, cUI As Long
    For k = 1 To nE
        If ext(k).Status = "" Then cUE = cUE + 1
    Next k
    For k = 1 To nI
        If itn(k).Status = "" Then cUI = cUI + 1
    Next k
    On Error Resume Next
    dash.Range("LastRun").Value = Now
    dash.Range("LastMatched").Value = nM
    dash.Range("LastAmtDiff").Value = nD
    dash.Range("LastUnmatchedExt").Value = cUE
    dash.Range("LastUnmatchedInt").Value = cUI
    dash.Range("LastBalDiff").Value = eClose + iClose
    On Error GoTo 0
End Sub
