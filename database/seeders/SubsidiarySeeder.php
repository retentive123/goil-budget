<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubsidiarySeeder extends Seeder
{
    // ── Helper: upsert an account_category row, return its ID ─────────────
    private function upsertCategory(string $code, string $name, string $budgetType): int
    {
        DB::table('account_categories')->updateOrInsert(
            ['code' => $code],
            [
                'name'        => $name,
                'code'        => $code,
                'budget_type' => $budgetType,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
        return DB::table('account_categories')->where('code', $code)->value('id');
    }

    // ── Helper: upsert an account_code row (any scope) ────────────────────
    private function upsertCode(string $code, string $name, int $categoryId, string $scope = 'subsidiary'): int
    {
        DB::table('account_codes')->updateOrInsert(
            ['code' => $code, 'scope' => $scope],
            [
                'name'                => $name,
                'account_category_id' => $categoryId,
                'scope'               => $scope,
                'is_active'           => true,
                'sort_order'          => 0,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]
        );
        return DB::table('account_codes')
            ->where('code', $code)->where('scope', $scope)
            ->value('id');
    }

    // ── Helper: replace all code assignments for a subsidiary ─────────────
    private function assignCodes(int $subsidiaryId, array $codeIds): void
    {
        DB::table('subsidiary_account_codes')
            ->where('subsidiary_id', $subsidiaryId)
            ->delete();

        $rows = [];
        foreach (array_unique(array_filter($codeIds)) as $codeId) {
            $rows[] = [
                'subsidiary_id'   => $subsidiaryId,
                'account_code_id' => $codeId,
                'is_active'       => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        if ($rows) {
            DB::table('subsidiary_account_codes')->insert($rows);
        }
    }

    // ── Main ───────────────────────────────────────────────────────────────
    public function run(): void
    {
        // ── 1. Subsidiary category ─────────────────────────────────────────
        $categoryId = DB::table('subsidiary_categories')
            ->where('name', 'Subsidiary Companies')
            ->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('subsidiary_categories')->insertGetId([
                'name'        => 'Subsidiary Companies',
                'description' => 'GOIL subsidiary companies with their own budget tracking.',
                'is_active'   => 1,
                'sort_order'  => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // ── 2. Go Energy Ltd ───────────────────────────────────────────────
        $gelId = DB::table('subsidiaries')->where('code', 'GEL')->value('id');
        if (! $gelId) {
            $gelId = DB::table('subsidiaries')->insertGetId([
                'subsidiary_category_id' => $categoryId,
                'name'                   => 'Go Energy Ltd',
                'code'                   => 'GEL',
                'description'            => 'Go Energy Limited – petroleum downstream/terminal subsidiary.',
                'is_active'              => 1,
                'sort_order'             => 1,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }

        // ── 3. GOIL Upstream Ltd ───────────────────────────────────────────
        $gulId = DB::table('subsidiaries')->where('code', 'GUL')->value('id');
        if (! $gulId) {
            $gulId = DB::table('subsidiaries')->insertGetId([
                'subsidiary_category_id' => $categoryId,
                'name'                   => 'GOIL Upstream Ltd',
                'code'                   => 'GUL',
                'description'            => 'GOIL Upstream Limited – upstream exploration and production subsidiary.',
                'is_active'              => 1,
                'sort_order'             => 2,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }

        // ── 4. GOIL Bitumen Ltd ────────────────────────────────────────────
        $gobId = DB::table('subsidiaries')->where('code', 'GOB')->value('id');
        if (! $gobId) {
            $gobId = DB::table('subsidiaries')->insertGetId([
                'subsidiary_category_id' => $categoryId,
                'name'                   => 'GOIL Bitumen Ltd',
                'code'                   => 'GOB',
                'description'            => 'GOIL Bitumen Limited – bitumen processing and sales subsidiary.',
                'is_active'              => 1,
                'sort_order'             => 3,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }

        // ==================================================================
        // GEL — GoEnergy-specific account categories (separate from GOIL)
        // ==================================================================
        // GEL-EXP-01..16 mirror the 16 recurrent expense sections in the
        // GoEnergy budget template. GEL-CAP-A..E mirror the 5 capex sections.
        // GEL-REV and GEL-COS are GoEnergy's revenue and cost of sales.
        // These are intentionally distinct from GOIL's CAT-01..CAT-21.

        $gelCats = [
            // Expense sections (match GoEnergy's 16 recurrent expense sections)
            'GEL-EXP-01' => ['Salaries & Allowances',            'expense'],
            'GEL-EXP-02' => ['Domestic Travelling & Transport',   'expense'],
            'GEL-EXP-03' => ['Other Personnel Cost',              'expense'],
            'GEL-EXP-04' => ['Various Materials',                 'expense'],
            'GEL-EXP-05' => ['Utilities',                         'expense'],
            'GEL-EXP-06' => ['Maintenance & Running Cost',        'expense'],
            'GEL-EXP-07' => ['Various Services',                  'expense'],
            'GEL-EXP-08' => ['Professional Services',             'expense'],
            'GEL-EXP-09' => ['Taxes / Levies / Permits',          'expense'],
            'GEL-EXP-10' => ['Equipment Hiring & Rental',         'expense'],
            'GEL-EXP-11' => ['Insurance Premium',                 'expense'],
            'GEL-EXP-12' => ['Sales Promotion & Advertising',     'expense'],
            'GEL-EXP-13' => ['Other Costs',                       'expense'],
            'GEL-EXP-14' => ['Depreciation of Fixed Assets',      'expense'],
            'GEL-EXP-15' => ['Financial Charges',                 'expense'],
            'GEL-EXP-16' => ['Health, Safety & Environment',      'expense'],
            // Capital sections (match GoEnergy's 5 capex sections A–E)
            'GEL-CAP-A'  => ['Construction Works',                'capital_expenditure'],
            'GEL-CAP-B'  => ['Terminal & Operational Equipment',  'capital_expenditure'],
            'GEL-CAP-C'  => ['Motor Vehicles',                    'capital_expenditure'],
            'GEL-CAP-D'  => ['Computers & Software',              'capital_expenditure'],
            'GEL-CAP-E'  => ['HSSE & Quality',                    'capital_expenditure'],
            // Revenue & Cost of Sales
            'GEL-REV'    => ['Revenue – Petroleum Products',      'revenue'],
            'GEL-COS'    => ['Cost of Sales – Petroleum Products', 'expense'],
        ];

        $gelCatIds = [];
        foreach ($gelCats as $code => [$name, $type]) {
            $gelCatIds[$code] = $this->upsertCategory($code, $name, $type);
        }

        $this->command->info('  ' . count($gelCatIds) . ' GoEnergy account categories upserted.');

        // ==================================================================
        // GEL — All account codes, grouped by category
        // ==================================================================
        // Format: [category_code, account_code, description]
        // Items marked (generated) have no code in the GoEnergy template;
        // they receive a GEL-EXP-xxxx / GEL-CAP-xxx code.

        $gelCodes = [

            // ── Section 1: Salaries & Allowances ─────────────────────────
            ['GEL-EXP-01', '60601000',    'Normal Salaries'],
            ['GEL-EXP-01', '60600000',    'Social Security Contribution (13%)'],
            ['GEL-EXP-01', '60610007',    '10% PF'],
            ['GEL-EXP-01', '60610000',    'Annual Bonus'],
            ['GEL-EXP-01', '60610006',    '13th Month Salary'],
            ['GEL-EXP-01', '60601003',    'Other Allowances'],

            // ── Section 2: Domestic Travelling & Transport ────────────────
            ['GEL-EXP-02', '60801000',    'Travelling, Mission and Accommodation'],

            // ── Section 3: Other Personnel Cost ──────────────────────────
            ['GEL-EXP-03', '60804000',    'International Travels (Ticketing/Accommodation)'],
            ['GEL-EXP-03', 'GEL-EXP-0301', 'Staff Training - External (Fees/Per Diem)'],
            ['GEL-EXP-03', 'GEL-EXP-0302', 'Other External Training (GOIL)'],
            ['GEL-EXP-03', 'GEL-EXP-0303', 'External Business (Local Travel)'],
            ['GEL-EXP-03', '60901000',    'Staff Training Expenses (Local)'],
            ['GEL-EXP-03', '60607000',    'Medical Expenses'],
            ['GEL-EXP-03', 'GEL-EXP-0304', 'Medical Examination (Yearly)'],
            ['GEL-EXP-03', '65300000',    'Head Office Rent'],
            ['GEL-EXP-03', '61601000',    'Laundry Expenses (Admin)'],
            ['GEL-EXP-03', '60612000',    'Subsidised Meals'],
            ['GEL-EXP-03', '6061000',     'End of Year Activities / Long Service Award'],
            ['GEL-EXP-03', '60608000',    'Staff Welfare and Food Package'],
            ['GEL-EXP-03', '60609001',    'Fitness & Recreation Assistance'],

            // ── Section 4: Various Materials ─────────────────────────────
            ['GEL-EXP-04', '60120001',    'Materials to Warehouse'],
            ['GEL-EXP-04', '62300000',    'Stationery & Printing'],
            ['GEL-EXP-04', '62310000',    'Computer Forms and Accessories'],
            ['GEL-EXP-04', '60609000',    'Uniform For Friday Wear'],

            // ── Section 5: Utilities ──────────────────────────────────────
            ['GEL-EXP-05', '62401000',    'Electricity - Offices'],
            ['GEL-EXP-05', '62402000',    'Electricity - Houses'],
            ['GEL-EXP-05', '65102001',    'Electricity - Depots'],
            ['GEL-EXP-05', '62501000',    'Water - Offices'],
            ['GEL-EXP-05', '65102002',    'Water - Depots'],
            ['GEL-EXP-05', '62502000',    'Water - Houses'],

            // ── Section 6: Maintenance & Running Cost ─────────────────────
            ['GEL-EXP-06', '60500000',    'Vehicle Maintenance'],
            ['GEL-EXP-06', 'GEL-EXP-0601', 'Terminal Maintenance'],
            ['GEL-EXP-06', '60500004',    'Cost of Lubes Consumed'],
            ['GEL-EXP-06', '60500005',    'Internal Consumption - Vehicle Fuel / Running Cost'],
            ['GEL-EXP-06', '61502000',    'Office Maintenance'],
            ['GEL-EXP-06', '61501000',    'General Office Equipment Maintenance'],
            ['GEL-EXP-06', '61506000',    'Computer, Software & Network Maintenance'],
            ['GEL-EXP-06', '61508000',    'Software License (ERP/Microsoft)'],
            ['GEL-EXP-06', '61503000',    'Executive House Maintenance'],

            // ── Section 7: Various Services ───────────────────────────────
            ['GEL-EXP-07', '65200000',    'Security & Other Services'],
            ['GEL-EXP-07', '60606000',    'Casual Labour'],
            ['GEL-EXP-07', '61504000',    'Janitorial and Ground Maintenance'],

            // ── Section 8: Professional Services ─────────────────────────
            ['GEL-EXP-08', '60701000',    'Directors Remuneration'],
            ['GEL-EXP-08', '60702000',    'Directors Other Expenses'],
            ['GEL-EXP-08', '61401000',    'Legal Expenses & Stamp Duty'],
            ['GEL-EXP-08', '61402000',    'Auditors Fees'],
            ['GEL-EXP-08', '651030',      'Quantity & Quality Third Party Survey'],
            ['GEL-EXP-08', '61408000',    'Standard Authority & Statutory Inspection Fees'],
            ['GEL-EXP-08', '61404000',    'Various Professional & Technical Services'],
            ['GEL-EXP-08', '69100000',    'Stock Exchange Expenses'],
            ['GEL-EXP-08', '69200000',    'Annual General Meeting'],

            // ── Section 9: Taxes / Levies / Permits ──────────────────────
            ['GEL-EXP-09', '65202002',    'Property Rates'],
            ['GEL-EXP-09', '61604000',    'Vehicle Licence / Road Worthy'],

            // ── Section 10: Equipment Hiring & Rental ────────────────────
            ['GEL-EXP-10', '65300001',    'Building Rents / Offices'],
            ['GEL-EXP-10', '61603000',    'Equipment Hiring'],
            ['GEL-EXP-10', '60611000',    'Courier & Dispatch / Telephone'],

            // ── Section 11: Insurance Premium ────────────────────────────
            ['GEL-EXP-11', '61101000',    'Fire & Burglary Insurance'],
            ['GEL-EXP-11', '61103000',    'Vehicle Insurance'],
            ['GEL-EXP-11', '61106000',    'Product at Depots Insurance'],
            ['GEL-EXP-11', '61108000',    'Directors Indemnity Insurance'],
            ['GEL-EXP-11', '61102000',    'Group Personal Accident Insurance'],
            ['GEL-EXP-11', '61109001',    'Group Life Insurance (Comprehensive)'],

            // ── Section 12: Sales Promotion & Advertising ────────────────
            ['GEL-EXP-12', '61301000',    'Advertising'],
            ['GEL-EXP-12', '61303000',    'Seasonal / End-of-Year Promotions'],
            ['GEL-EXP-12', '61302000',    'Sales & Business Promotion'],

            // ── Section 13: Other Costs ───────────────────────────────────
            ['GEL-EXP-13', '62700000',    'Subscriptions'],
            ['GEL-EXP-13', '62100000',    'Specific Charity / Sponsorship'],
            ['GEL-EXP-13', '62800000',    'Donations'],
            ['GEL-EXP-13', '62900000',    'Entertainment'],
            ['GEL-EXP-13', '61607000',    'Commercial Information'],
            ['GEL-EXP-13', 'GEL-EXP-1301', 'Honorarium'],
            ['GEL-EXP-13', '62990000',    'Archiving'],

            // ── Section 15: Financial Charges ────────────────────────────
            ['GEL-EXP-15', '61800000',    'Exchange Loss & Inventory Losses'],
            ['GEL-EXP-15', '63000000',    'Financial Charges'],

            // ── Section 16: Health, Safety & Environment ──────────────────
            ['GEL-EXP-16', '65108000',       'HSE Programmes'],
            ['GEL-EXP-16', 'GEL-EXP-1601',  'Drills, Exercises and Simulations'],
            ['GEL-EXP-16', 'GEL-EXP-1602',  'Personal Protective Equipment (PPE)'],
            ['GEL-EXP-16', 'GEL-EXP-1603',  'Oil Spill Emergency Response Equipment Maintenance'],
            ['GEL-EXP-16', 'GEL-EXP-1604',  'HSSE General Equipment Maintenance and Accessories'],
            ['GEL-EXP-16', 'GEL-EXP-1605',  'HSSE Signages (Markings, Signboards, Posters)'],
            ['GEL-EXP-16', 'GEL-EXP-1606',  'HSSE Awards and Incentives'],
            ['GEL-EXP-16', 'GEL-EXP-1607',  'Fire Protection'],
            ['GEL-EXP-16', 'GEL-EXP-1608',  'Security and Safety'],

            // ── Capital: A. Construction Works ────────────────────────────
            ['GEL-CAP-A', 'GEL-CAP-A01', 'New Fuel Receipt Line at the Reclaimed Land (Log Pond)'],
            ['GEL-CAP-A', 'GEL-CAP-A02', 'Extension of TDH Office and Accessories'],
            ['GEL-CAP-A', 'GEL-CAP-A03', 'Upgrade of Bondwalls - Sekondi Takoradi'],

            // ── Capital: B. Terminal & Operational Equipment ──────────────
            ['GEL-CAP-B', 'GEL-CAP-B01', 'Firefighting Pipeline Upgrade Takoradi'],
            ['GEL-CAP-B', 'GEL-CAP-B02', 'Firefighting Unit Upgrade Sekondi'],
            ['GEL-CAP-B', 'GEL-CAP-B03', 'Power and Comms Cable Restoration Takoradi'],
            ['GEL-CAP-B', 'GEL-CAP-B04', 'Power and Comms Cable Restoration Sekondi'],
            ['GEL-CAP-B', 'GEL-CAP-B05', 'TDH ATEC Light'],
            ['GEL-CAP-B', 'GEL-CAP-B06', 'Yokogawa Systems Re-activation'],
            ['GEL-CAP-B', 'GEL-CAP-B07', 'SNB SCADA System Software Upgrade'],
            ['GEL-CAP-B', 'GEL-CAP-B08', '6 inch Receiving Hose (4x40m)'],

            // ── Capital: C. Motor Vehicles ────────────────────────────────
            ['GEL-CAP-C', 'GEL-CAP-C01', 'L200 Pickup (4x4)'],
            ['GEL-CAP-C', 'GEL-CAP-C02', 'i10 Hyundai'],

            // ── Capital: D. Computers & Software ─────────────────────────
            ['GEL-CAP-D', 'GEL-CAP-D01', 'Enterprise Systems (ERPs)'],
            ['GEL-CAP-D', 'GEL-CAP-D02', 'Network Infrastructure'],
            ['GEL-CAP-D', 'GEL-CAP-D03', 'Laser Printer'],
            ['GEL-CAP-D', 'GEL-CAP-D04', 'Laptop'],
            ['GEL-CAP-D', 'GEL-CAP-D05', 'Access Control'],
            ['GEL-CAP-D', 'GEL-CAP-D06', 'Desktop Computer'],
            ['GEL-CAP-D', 'GEL-CAP-D07', 'Computer Server'],
            ['GEL-CAP-D', 'GEL-CAP-D08', 'Scanners'],
            ['GEL-CAP-D', 'GEL-CAP-D09', 'UPS Devices'],

            // ── Capital: E. HSSE & Quality ────────────────────────────────
            ['GEL-CAP-E', 'GEL-CAP-E01', 'CCTV Upgrade'],
            ['GEL-CAP-E', 'GEL-CAP-E02', 'HSSE ISO Certification'],
            ['GEL-CAP-E', 'GEL-CAP-E03', 'Incident Reporting System'],
            ['GEL-CAP-E', 'GEL-CAP-E04', 'CMP/IMS - Implementation'],
            ['GEL-CAP-E', 'GEL-CAP-E05', 'Intrinsically Safe Atex Camera / Explosion Proof Tablet'],
            ['GEL-CAP-E', 'GEL-CAP-E06', 'Thickness Gauge Device'],

            // ── Revenue: Petroleum Products ───────────────────────────────
            ['GEL-REV', 'GEL-REV-PMS',  'Revenue - Gasoline (PMS)'],
            ['GEL-REV', 'GEL-REV-AGO',  'Revenue - Gasoil (AGO)'],
            ['GEL-REV', 'GEL-REV-MGOL', 'Revenue - Marine Gasoil (Local)'],
            ['GEL-REV', 'GEL-REV-MGOF', 'Revenue - Marine Gasoil (Rig & Foreign)'],
            ['GEL-REV', 'GEL-REV-AGOX', 'Revenue - AGO Export'],
            ['GEL-REV', 'GEL-REV-AGON', 'Revenue - AGO Mines'],
            ['GEL-REV', 'GEL-REV-ATK',  'Revenue - Jet A1'],
            ['GEL-REV', 'GEL-REV-HFO',  'Revenue - HFO'],
            ['GEL-REV', 'GEL-REV-LPG',  'Revenue - LPGas'],

            // ── Cost of Sales: Petroleum Products ────────────────────────
            ['GEL-COS', 'GEL-COS-PMS',  'Cost of Sales - Gasoline (PMS)'],
            ['GEL-COS', 'GEL-COS-AGO',  'Cost of Sales - Gasoil (AGO)'],
            ['GEL-COS', 'GEL-COS-MGOL', 'Cost of Sales - Marine Gasoil (Local)'],
            ['GEL-COS', 'GEL-COS-MGOF', 'Cost of Sales - Marine Gasoil (Rig & Foreign)'],
            ['GEL-COS', 'GEL-COS-AGOX', 'Cost of Sales - AGO Export'],
            ['GEL-COS', 'GEL-COS-AGON', 'Cost of Sales - AGO Mines'],
            ['GEL-COS', 'GEL-COS-ATK',  'Cost of Sales - Jet A1'],
            ['GEL-COS', 'GEL-COS-HFO',  'Cost of Sales - HFO'],
            ['GEL-COS', 'GEL-COS-LPG',  'Cost of Sales - LPGas'],
        ];

        $gelCodeIds = [];
        foreach ($gelCodes as [$catCode, $code, $name]) {
            $catId = $gelCatIds[$catCode] ?? null;
            if (! $catId) {
                $this->command->warn("  GEL: category {$catCode} not found — skipping {$code}");
                continue;
            }
            $gelCodeIds[] = $this->upsertCode($code, $name, $catId);
        }

        $this->command->info('  ' . count($gelCodeIds) . ' GoEnergy account codes upserted.');

        // ==================================================================
        // ASSIGN CODES TO GO ENERGY LTD (GEL)
        // ==================================================================
        $this->assignCodes($gelId, $gelCodeIds);

        // ==================================================================
        // GUL — GOIL Upstream Ltd: subsidiary-specific categories & codes
        // All codes are scope='subsidiary' — no GOIL department codes shared.
        // ==================================================================

        // ── GUL account categories ─────────────────────────────────────────
        $gulCats = [
            // Capital
            'GUL-CAP-GG' => ['Geological & Geophysical',                  'capital_expenditure'],
            'GUL-CAP-DR' => ['Drilling & Testing',                         'capital_expenditure'],
            'GUL-CAP-FE' => ['Field Evaluation',                           'capital_expenditure'],
            'GUL-CAP-IT' => ['IT & Equipment – Capital',                   'capital_expenditure'],
            // Recurrent
            'GUL-EXP-01' => ['Office & Facilities',                        'expense'],
            'GUL-EXP-02' => ['G&G Software & IT Systems',                  'expense'],
            'GUL-EXP-03' => ['Communications',                             'expense'],
            'GUL-EXP-04' => ['Staff Costs / Manpower',                     'expense'],
            'GUL-EXP-05' => ['Block Promotion',                            'expense'],
            'GUL-EXP-06' => ['Corporate Social Responsibility',            'expense'],
            'GUL-EXP-07' => ['Travel & Entertainment',                     'expense'],
            'GUL-EXP-08' => ['Training & Development',                     'expense'],
            'GUL-EXP-09' => ['Subscriptions & Memberships',                'expense'],
            'GUL-EXP-10' => ['Insurance',                                  'expense'],
            'GUL-EXP-11' => ['Professional Fees & Governance',             'expense'],
            'GUL-EXP-12' => ['Financial Charges',                          'expense'],
            'GUL-EXP-13' => ['PA Obligations / Licence Fees',              'expense'],
            'GUL-EXP-14' => ['Indirect Costs',                             'expense'],
        ];

        $gulCatIds = [];
        foreach ($gulCats as $catCode => [$catName, $budgetType]) {
            $gulCatIds[$catCode] = $this->upsertCategory($catCode, $catName, $budgetType);
        }

        $this->command->info('  ' . count($gulCatIds) . ' GUL-specific categories upserted.');

        // ── GUL-specific codes  [category_code, account_code, description] ──
        // Only items that have no equivalent in the GoEnergy (GEL) subsidiary codes.
        // Generic admin codes shared with GEL are assigned via $gelSharedForGul below.
        $gulCodes = [
            // ── 1.1 Geological & Geophysical (CAPEX) ─────────────────────
            ['GUL-CAP-GG', 'GUL-G001', 'G&G – Seismic Data Acquisition & Processing'],
            ['GUL-CAP-GG', 'GUL-G002', 'G&G – Special Studies'],
            ['GUL-CAP-GG', 'GUL-G003', 'G&G – Exploration Operations'],
            ['GUL-CAP-GG', 'GUL-G004', 'G&G – Exploration Well Reservoir Engineering Study'],
            ['GUL-CAP-GG', 'GUL-G005', 'G&G – Technical Advisory Services'],
            ['GUL-CAP-GG', 'GUL-G006', 'G&G – Pre-Well Programme'],
            ['GUL-CAP-GG', 'GUL-G007', 'G&G – Structural Analysis Wellhead / Tree'],
            ['GUL-CAP-GG', 'GUL-G008', 'G&G – Riser Analyses'],
            // ── 1.2 Drilling & Testing (CAPEX) ───────────────────────────
            ['GUL-CAP-DR', 'GUL-D001', 'Drilling – Preparation'],
            ['GUL-CAP-DR', 'GUL-D002', 'Drilling – Permitting'],
            ['GUL-CAP-DR', 'GUL-D003', 'Drilling – Mobilisation & Demobilisation'],
            ['GUL-CAP-DR', 'GUL-D004', 'Drilling – Well Cost'],
            ['GUL-CAP-DR', 'GUL-D005', 'Drilling – Well Test'],
            // ── 1.3 Field Evaluation (CAPEX) ─────────────────────────────
            ['GUL-CAP-FE', 'GUL-F001', 'Field Evaluation – Future Developments'],
            ['GUL-CAP-FE', 'GUL-F002', 'Reservoir Development Studies'],
            // ── 1.4.2 Capital Hardware ────────────────────────────────────
            ['GUL-CAP-IT', 'GUL-HW001', 'Hi-End Workstations, Laptops & Accessories (Capital)'],
            // ── 2.1 Office — items with no GEL equivalent ─────────────────
            ['GUL-EXP-01', 'GUL-OFF-003', 'Catering Services'],
            ['GUL-EXP-01', 'GUL-OFF-004', 'Cleaning Services'],
            ['GUL-EXP-01', 'GUL-OFF-005', 'General Administration Costs'],
            ['GUL-EXP-01', 'GUL-UT001',   'Utilities – Heating / Other'],
            ['GUL-EXP-01', 'GUL-OFF-008', 'Office Fuel for Internal Consumption'],
            ['GUL-EXP-01', 'GUL-OFF-009', 'Office Service / Property Management'],
            ['GUL-EXP-01', 'GUL-OF001',   'Office Storage'],
            ['GUL-EXP-01', 'GUL-OF002',   'Data Filing & Records Management'],
            ['GUL-EXP-01', 'GUL-OFF-010', 'Sundry Office Costs'],
            ['GUL-EXP-01', 'GUL-OFF-011', 'Licences and Permits'],
            ['GUL-EXP-01', 'GUL-OF003',   'Office Health & Safety'],
            ['GUL-EXP-01', 'GUL-OFF-015', 'Residential Repairs'],
            ['GUL-EXP-01', 'GUL-OFF-017', 'Accrued Office Expenditures'],
            ['GUL-EXP-01', 'GUL-OFF-018', 'Office – Other Costs'],
            // ── 1.4.1 G&G Software licences (upstream-specific) ──────────
            ['GUL-EXP-02', 'GUL-SW001',   'G&G Scientific Software – Licences & Maintenance'],
            ['GUL-EXP-02', 'GUL-SW002',   'Technical Databases'],
            ['GUL-EXP-02', 'GUL-SW003',   'T-Navigator Software Licence'],
            ['GUL-EXP-02', 'GUL-SW004',   'Kingdom / IHS G&G Software Licences (EarthPAK)'],
            ['GUL-EXP-02', 'GUL-SW005',   'IHSKingdom Seismic Inversion Licence'],
            ['GUL-EXP-02', 'GUL-SW006',   'IHSKingdom AVOPAK Licence'],
            ['GUL-EXP-02', 'GUL-SW007',   'IHSKingdom Petrophysics Licence'],
            // ── 1.4.3 IT Systems Maintenance (upstream-specific) ──────────
            ['GUL-EXP-02', 'GUL-IT001',   'Server Systems Maintenance & Upgrade'],
            ['GUL-EXP-02', 'GUL-IT002',   'IHSKingdom Software Maintenance'],
            ['GUL-EXP-02', 'GUL-IT003',   'vSphere Licence Renewal'],
            ['GUL-EXP-02', 'GUL-IT004',   'SLA Monthly Systems Maintenance'],
            ['GUL-EXP-02', 'GUL-IT005',   'Veeam Data Platform Renewal / Maintenance'],
            ['GUL-EXP-02', 'GUL-IT006',   'HPE Support Renewal'],
            ['GUL-EXP-02', 'GUL-IT007',   'Mbal Software Maintenance'],
            ['GUL-EXP-02', 'GUL-IT008',   'Zebra Data Services'],
            ['GUL-EXP-02', 'GUL-IT009',   'Cisco Hardware Support Renewal'],
            ['GUL-EXP-02', 'GUL-IT010',   'VMware Renewal'],
            ['GUL-EXP-02', 'GUL-IT011',   'Geoscience Software Maintenance'],
            ['GUL-EXP-02', 'GUL-IT012',   'Data Communication Installation'],
            // ── 2.1.4 Computer Expenses — items beyond GEL coverage ──────
            ['GUL-EXP-02', 'GUL-COM-003', 'Hardware Maintenance'],
            ['GUL-EXP-02', 'GUL-COM-004', 'Systems Support'],
            ['GUL-EXP-02', 'GUL-COM-005', 'Data Communication Charges / Rental'],
            ['GUL-EXP-02', 'GUL-COM-006', 'IT Consumables'],
            ['GUL-EXP-02', 'GUL-COM-007', 'Expensed Hardware (below capitalisation threshold)'],
            ['GUL-EXP-02', 'GUL-COM-008', 'Software Operating Lease / Rental'],
            ['GUL-EXP-02', 'GUL-COM-009', 'Offsite Data Storage'],
            ['GUL-EXP-02', 'GUL-COM-010', 'Hardware Lease / Rental'],
            // ── 2.1.5 Communications — items beyond GEL 60611000 ─────────
            ['GUL-EXP-03', 'GUL-TEL-001', 'Telephone (Landline)'],
            ['GUL-EXP-03', 'GUL-TEL-002', 'Facsimile'],
            ['GUL-EXP-03', 'GUL-TEL-005', 'Mobile Telephones'],
            ['GUL-EXP-03', 'GUL-TEL-006', 'Internet Expenses'],
            // ── 2.3 Block Promotion ───────────────────────────────────────
            ['GUL-EXP-05', 'GUL-PR001',   'Block Promotion – Public Relations'],
            ['GUL-EXP-05', 'GUL-PR002',   'Stakeholder Engagement'],
            ['GUL-EXP-05', 'GUL-MK001',   'Block Promotion – Direct Marketing'],
            ['GUL-EXP-05', 'GUL-PRM-001', 'Consultancy & Negotiations'],
            ['GUL-EXP-05', 'GUL-PRM-002', 'Advertisement / Article Publications'],
            ['GUL-EXP-05', 'GUL-PRM-003', 'Business Promotion'],
            // ── 2.4 CSR (2.4.7 Sponsorships uses GEL 62100000) ───────────
            ['GUL-EXP-06', 'GUL-CSR001',  'CSR – Community Healthcare & Education Support'],
            ['GUL-EXP-06', 'GUL-CSR002',  'CSR – Corporate Gifts'],
            ['GUL-EXP-06', 'GUL-CSR003',  'CSR – Enterprise & Business Development Support'],
            ['GUL-EXP-06', 'GUL-CSR004',  'CSR – Community Engagement Programs'],
            ['GUL-EXP-06', 'GUL-CSR005',  'CSR – Social Community Projects (incl. Street Kids & Shoes)'],
            // ── 2.5 Travel — items beyond GEL 60500000 / 62900000 ────────
            ['GUL-EXP-07', 'GUL-TR001',   'Travel – Visa Costs'],
            ['GUL-EXP-07', 'GUL-TR002',   'Travel Clearing Account'],
            ['GUL-EXP-07', 'GUL-TRV-001', 'Car Hire / Vehicle Costs'],
            ['GUL-EXP-07', 'GUL-TRV-002', 'Flights (Local)'],
            ['GUL-EXP-07', 'GUL-TRV-003', 'Hotels & Subsistence (Local)'],
            ['GUL-EXP-07', 'GUL-TRV-004', 'Taxi / Rail & Parking'],
            ['GUL-EXP-07', 'GUL-TRV-007', 'Vehicle Miscellaneous'],
            ['GUL-EXP-07', 'GUL-TRV-008', 'Vehicle Lease Costs – Monthly'],
            ['GUL-EXP-07', 'GUL-TRV-009', 'Vehicle Lease Costs – Replacements'],
            ['GUL-EXP-07', 'GUL-TRV-010', 'Staff Sports & Social Club'],
            ['GUL-EXP-07', 'GUL-TRV-011', 'Business Trip Incidentals'],
            ['GUL-EXP-07', 'GUL-TRV-013', 'Flights (Overseas)'],
            ['GUL-EXP-07', 'GUL-TRV-014', 'Hotels & Subsistence (Overseas)'],
            ['GUL-EXP-07', 'GUL-TRV-015', 'Taxi / Parking (Overseas)'],
            ['GUL-EXP-07', 'GUL-TRV-016', 'Overseas Business Trip Incidentals'],
            ['GUL-EXP-07', 'GUL-TRV-018', 'Flights (Training)'],
            ['GUL-EXP-07', 'GUL-TRV-019', 'Hotels & Subsistence (Training)'],
            ['GUL-EXP-07', 'GUL-TRV-020', 'Seasonal & End of Year Activities'],
            // ── 2.6 Training & Development ────────────────────────────────
            ['GUL-EXP-08', 'GUL-HR001',   'Recruitment'],
            ['GUL-EXP-08', 'GUL-HR002',   'Staff Study & Exam Costs'],
            ['GUL-EXP-08', 'GUL-HR003',   'Payroll Giving Matching'],
            ['GUL-EXP-08', 'GUL-TRN-002', 'Conferences & Seminars'],
            // ── 2.7 Subscriptions — items beyond GEL 62700000 / 62800000 ─
            ['GUL-EXP-09', 'GUL-SUB001',  'Books, Manuals & Technical Reports'],
            ['GUL-EXP-09', 'GUL-CSR002',  'Corporate Gifts'],
            ['GUL-EXP-09', 'GUL-SUB-005', 'Corporate Social Responsibility (General)'],
            ['GUL-EXP-09', 'GUL-SUB-006', 'Subscriptions & Donations – Accruals'],
            // ── 2.8 Insurance — items beyond GEL 61101000 / 61103000 / 61102000 ─
            ['GUL-EXP-10', 'GUL-INS-001', 'Insurance (General / All Risks)'],
            ['GUL-EXP-10', 'GUL-INS-004', 'Computer & Equipment Insurance'],
            ['GUL-EXP-10', 'GUL-INS-005', 'Building Contents Insurance'],
            ['GUL-EXP-10', 'GUL-INS-006', 'Public Liability Insurance'],
            ['GUL-EXP-10', 'GUL-INS-008', 'Insurance – Accrued Expenditures'],
            // ── 2.9 Professional Fees — items beyond GEL 61402000 etc. ───
            ['GUL-EXP-11', 'GUL-FIN001',  'Pension Administration Fees'],
            ['GUL-EXP-11', 'GUL-PRF-002', 'Tax Fees (Auditors & Other)'],
            // ── 2.10 Financial Charges (GUL template has 4 separate lines) ─
            ['GUL-EXP-12', 'GUL-FIN-001', 'Bank Charges'],
            ['GUL-EXP-12', 'GUL-FIN-002', 'Credit Card Fees'],
            ['GUL-EXP-12', 'GUL-FIN-003', 'Lease Finance Charges'],
            ['GUL-EXP-12', 'GUL-FIN-004', 'Stamp & Other Duties'],
            // ── 3 PA Obligations / Licence Fees ──────────────────────────
            ['GUL-EXP-13', 'GUL-PA001',   'PA Obligation – Annual Surface Rentals'],
            ['GUL-EXP-13', 'GUL-PA002',   'PA Obligation – Annual Training Fee'],
            ['GUL-EXP-13', 'GUL-PA003',   'PA Obligation – PC Permit'],
            // ── 2.11 Indirect Costs ───────────────────────────────────────
            ['GUL-EXP-14', 'GUL-IND001',  'Indirect Costs – Overhead'],
        ];

        $gulCodeIds = [];
        foreach ($gulCodes as [$catCode, $code, $name]) {
            $catId = $gulCatIds[$catCode] ?? null;
            if (! $catId) {
                $this->command->warn("  GUL: category {$catCode} not found — skipping {$code}");
                continue;
            }
            $gulCodeIds[] = $this->upsertCode($code, $name, $catId);
        }

        $this->command->info('  ' . count($gulCodeIds) . ' GUL-specific codes upserted.');

        // ── GoEnergy codes that also apply to GUL (no duplication) ────────
        // These are scope='subsidiary' codes created for GEL that cover the
        // same expense concepts in the GUL CFO template. We assign them to
        // GUL directly rather than creating duplicate GUL-xxx codes.
        $gelSharedForGul = [
            // 2.2 Staff (all 28 named positions → Normal Salaries; 2.2.29 → Casual Labour)
            '60601000', // Normal Salaries
            '60606000', // Casual Labour / Support Staff
            // 2.1.1 Office Rent & Rates
            '65300001', // Building Rents / Offices
            '65202002', // Property Rates
            // 2.1.2 Office Services
            '62401000', // Electricity – Offices
            '62501000', // Water – Offices
            '65200000', // Security & Other Services
            // 2.1.3 Equipment Supply & Maintenance
            '62300000', // Stationery & Printing
            '61603000', // Equipment Hiring
            '61502000', // Office Maintenance
            '61501000', // General Office Equipment Maintenance
            // 2.1.4 Computer Expenses
            '61508000', // Software License (ERP / Microsoft) → New Software
            '61506000', // Computer, Software & Network Maintenance
            // 2.1.5 Communications
            '60611000', // Courier & Dispatch / Telephone (covers Postage + Courier)
            // 2.5 Travel & Entertainment
            '60500000', // Vehicle Maintenance (covers 2.5.5 + 2.5.6)
            '62900000', // Entertainment (covers 2.5.10, 2.5.13, 2.5.18)
            '60804000', // International Travels – Ticketing & Accommodation (2.5.14)
            // 2.6 Training
            '60901000', // Staff Training Expenses
            // 2.7 Subscriptions
            '62700000', // Subscriptions (2.7.1, 2.7.2)
            '62800000', // Donations (2.7.4, 2.7.5)
            '62100000', // Specific Charity / Sponsorship (2.4.7)
            // 2.8 Insurance
            '61101000', // Fire & Burglary Insurance → Building Insurance (2.8.2)
            '61103000', // Vehicle Insurance (2.8.3)
            '61102000', // Group Personal Accident Insurance (2.8.8)
            '61109001', // Group Life Insurance – Comprehensive (2.8.8)
            '60607000', // Medical Expenses (2.8.8 medical component)
            // 2.9 Professional Fees
            '61402000', // Auditors Fees (2.9.1)
            '61404000', // Various Professional & Technical Services (2.9.5)
            '61401000', // Legal Expenses & Stamp Duty (2.9.9)
            '60701000', // Directors Remuneration (2.9.10)
            '60702000', // Directors Other Expenses (2.9.10)
        ];

        $gelSharedIds = DB::table('account_codes')
            ->where('scope', 'subsidiary')
            ->whereIn('code', $gelSharedForGul)
            ->pluck('id')
            ->all();

        $missing = array_diff($gelSharedForGul, DB::table('account_codes')
            ->where('scope', 'subsidiary')
            ->whereIn('code', $gelSharedForGul)
            ->pluck('code')->all());
        foreach ($missing as $mc) {
            $this->command->warn("  GUL: GEL shared code {$mc} not found — skipping");
        }

        $this->command->info('  ' . count($gelSharedIds) . ' GEL codes also assigned to GUL.');

        // ==================================================================
        // ASSIGN CODES TO GOIL UPSTREAM LTD (GUL)
        // GUL-specific codes + GEL codes that cover shared template items
        // ==================================================================
        $gulAllIds = array_values(array_unique(array_merge($gulCodeIds, $gelSharedIds)));
        $this->assignCodes($gulId, $gulAllIds);

        // ==================================================================
        // GOIL BITUMEN LTD (GOB) — CATEGORIES
        // ==================================================================
        $gobCats = [];
        foreach ([
            // Revenue
            ['GOB-REV',    'Revenue',                          'revenue'],
            // COGS
            ['GOB-DM',     'COGS – Direct Materials',          'expense'],
            ['GOB-OH',     'COGS – Factory Overheads',         'expense'],
            // SG&A
            ['GOB-SGA',    'Selling, General & Administrative', 'expense'],
            // CAPEX
            ['GOB-CAP-FF', 'Fixtures & Fittings (CAPEX)',       'capital_expenditure'],
            ['GOB-CAP-IT', 'Computer & Accessories (CAPEX)',    'capital_expenditure'],
            ['GOB-CAP-EL', 'Equipment – Lab (CAPEX)',           'capital_expenditure'],
            ['GOB-CAP-SF', 'Equipment – Safety (CAPEX)',        'capital_expenditure'],
            ['GOB-CAP-PM', 'Plant & Machinery (CAPEX)',         'capital_expenditure'],
            ['GOB-CAP-MV', 'Motor Vehicles (CAPEX)',            'capital_expenditure'],
        ] as [$catCode, $catName, $budgetType]) {
            $gobCats[$catCode] = $this->upsertCategory($catCode, $catName, $budgetType);
        }

        $this->command->info('  ' . count($gobCats) . ' GOIL Bitumen account categories upserted.');

        // ==================================================================
        // GOIL BITUMEN LTD (GOB) — CODES
        // [category_code, account_code, description]
        // ==================================================================
        $gobCodes = [
            // ── Revenue (4) ───────────────────────────────────────────────
            ['GOB-REV', 'GOB-R001', 'Bitumen Emulsions – Revenue'],
            ['GOB-REV', 'GOB-R002', 'Polymer Modified Bitumen (PMB) – Revenue'],
            ['GOB-REV', 'GOB-R003', 'AC 10 – Revenue'],
            ['GOB-REV', 'GOB-R004', 'AC 20 – Revenue'],

            // ── COGS Direct Materials (11) ────────────────────────────────
            // Product volume / sales-mix drivers (4)
            ['GOB-DM', 'GOB-DM-001', 'Bitumen Emulsions – Production / Sales Volume'],
            ['GOB-DM', 'GOB-DM-002', 'PMB – Production Volume'],
            ['GOB-DM', 'GOB-DM-003', 'AC 10 – Direct Sales'],
            ['GOB-DM', 'GOB-DM-004', 'AC 20 – Direct Sales'],
            // Raw material cost lines (7)
            ['GOB-DM', 'GOB-DM-005', 'Direct Material – Amino'],
            ['GOB-DM', 'GOB-DM-006', 'Direct Material – HCL'],
            ['GOB-DM', 'GOB-DM-007', 'Direct Material – Production Water'],
            ['GOB-DM', 'GOB-DM-008', 'Direct Material – Polymer (SBS)'],
            ['GOB-DM', 'GOB-DM-009', 'Direct Material – Cross Link'],
            ['GOB-DM', 'GOB-DM-010', 'Direct Material – AC 10 (Raw Material)'],
            ['GOB-DM', 'GOB-DM-011', 'Direct Material – AC 20 (Raw Material)'],

            // ── Factory Overheads (11) ────────────────────────────────────
            ['GOB-OH', 'GOB-OH-001', 'Factory Labour (Direct & Indirect)'],
            ['GOB-OH', 'GOB-OH-002', 'HSSE – Factory'],
            ['GOB-OH', 'GOB-OH-003', 'Electricity – Factory / Production'],
            ['GOB-OH', 'GOB-OH-004', 'Fuel Consumption – Production'],
            ['GOB-OH', 'GOB-OH-005', 'Lab Expenses'],
            ['GOB-OH', 'GOB-OH-006', 'Maintenance – Plant, Machinery & Equipment'],
            ['GOB-OH', 'GOB-OH-007', 'Security Services – Factory'],
            ['GOB-OH', 'GOB-OH-008', 'Cleaning Services – Factory'],
            ['GOB-OH', 'GOB-OH-009', 'Permits & Licences – Factory'],
            ['GOB-OH', 'GOB-OH-010', 'Insurance – Factory / Production Assets'],
            ['GOB-OH', 'GOB-OH-011', 'Depreciation – Plant, Machinery & Equipment'],

            // ── SG&A — GOB-specific only (7); shared GEL codes added below ─
            ['GOB-SGA', 'GOB-SGA-001', 'Canteen / Staff Canteen'],
            ['GOB-SGA', 'GOB-SGA-002', 'Cleaning Services (Administrative)'],
            ['GOB-SGA', 'GOB-SGA-003', 'Transport Expenses (Admin)'],
            ['GOB-SGA', 'GOB-SGA-004', 'Business Promotion'],
            ['GOB-SGA', 'GOB-SGA-005', 'HSSE – Other (Administrative)'],
            ['GOB-SGA', 'GOB-SGA-006', 'Foreign Exchange Losses'],
            ['GOB-SGA', 'GOB-SGA-007', 'Depreciation – SG&A / Admin Assets'],

            // ── CAPEX: Fixtures & Fittings (15) ──────────────────────────
            ['GOB-CAP-FF', 'GOB-FF-001', 'Water Hydrant'],
            ['GOB-CAP-FF', 'GOB-FF-002', 'Access Control System'],
            ['GOB-CAP-FF', 'GOB-FF-003', 'Motion Sensors'],
            ['GOB-CAP-FF', 'GOB-FF-004', 'Raising of Panels, Junction Boxes, Transformers & Control Panels'],
            ['GOB-CAP-FF', 'GOB-FF-005', 'Raising of Server Rack in Server Room'],
            ['GOB-CAP-FF', 'GOB-FF-006', 'Fabricating of Waste Bins & Barrel Containment Trays'],
            ['GOB-CAP-FF', 'GOB-FF-007', 'Irrigation Set Up'],
            ['GOB-CAP-FF', 'GOB-FF-008', 'Water Source for Truck Park Washrooms'],
            ['GOB-CAP-FF', 'GOB-FF-009', 'Safety Signages and Labels'],
            ['GOB-CAP-FF', 'GOB-FF-010', 'Safety Equipment (Fixtures & Fittings)'],
            ['GOB-CAP-FF', 'GOB-FF-011', 'Dining Table'],
            ['GOB-CAP-FF', 'GOB-FF-012', 'Dining Table Chairs'],
            ['GOB-CAP-FF', 'GOB-FF-013', 'Lab Chairs'],
            ['GOB-CAP-FF', 'GOB-FF-014', 'Executive Swivel Chair'],
            ['GOB-CAP-FF', 'GOB-FF-015', 'Swivel Chairs'],

            // ── CAPEX: Computer & Accessories (3) ────────────────────────
            ['GOB-CAP-IT', 'GOB-IT-001', 'Laptop for Programming Controls'],
            ['GOB-CAP-IT', 'GOB-IT-002', 'Desktop Computer'],
            ['GOB-CAP-IT', 'GOB-IT-003', 'Laptop Computer'],

            // ── CAPEX: Equipment – Lab (5) ────────────────────────────────
            ['GOB-CAP-EL', 'GOB-EL-001', 'Fume Containment / Extraction System'],
            ['GOB-CAP-EL', 'GOB-EL-002', 'ATMO Agilis Bitumen Emulsion Pilot (Vialab)'],
            ['GOB-CAP-EL', 'GOB-EL-003', 'Moisture Analyser (Vialab)'],
            ['GOB-CAP-EL', 'GOB-EL-004', 'Settling Tendency Apparatus (Vialab)'],
            ['GOB-CAP-EL', 'GOB-EL-005', 'Breaking Index Apparatus (Vialab)'],

            // ── CAPEX: Equipment – Safety (3) ────────────────────────────
            ['GOB-CAP-SF', 'GOB-SF-001', 'Mobile First Aid Container'],
            ['GOB-CAP-SF', 'GOB-SF-002', 'Safety Equipment (Safety Category)'],
            ['GOB-CAP-SF', 'GOB-SF-003', 'Movable Security Post'],

            // ── CAPEX: Plant & Machinery (17) ────────────────────────────
            ['GOB-CAP-PM', 'GOB-PM-001', 'Thermometer Installation on Pipelines & Heat Tracing Cable Repair'],
            ['GOB-CAP-PM', 'GOB-PM-002', 'Plug Valves & Triac Pneumatic Actuators'],
            ['GOB-CAP-PM', 'GOB-PM-003', 'Fiber Optic Communication Cards (Red Lion Controls 106FX2-ST)'],
            ['GOB-CAP-PM', 'GOB-PM-004', 'VFD Communication Cards (Siemens CU240E-2 PN)'],
            ['GOB-CAP-PM', 'GOB-PM-005', 'ARI Actuators'],
            ['GOB-CAP-PM', 'GOB-PM-006', 'General Planned Spares'],
            ['GOB-CAP-PM', 'GOB-PM-007', 'Siemens PLC Profinet, Profibus & Cards (Set 1)'],
            ['GOB-CAP-PM', 'GOB-PM-008', 'Work Bench, Tools, Tools Rack & Shelter for Maintenance'],
            ['GOB-CAP-PM', 'GOB-PM-009', 'XPI-50 Cables (1000 m)'],
            ['GOB-CAP-PM', 'GOB-PM-010', 'XPI-31.5 Cables (1000 m)'],
            ['GOB-CAP-PM', 'GOB-PM-011', 'Joint Kits for XPI-50 & XPI-31.5 Cables (200 kits)'],
            ['GOB-CAP-PM', 'GOB-PM-012', 'Siemens PLC Profinet, Profibus & Cards (Set 2)'],
            ['GOB-CAP-PM', 'GOB-PM-013', 'Fuse Spares'],
            ['GOB-CAP-PM', 'GOB-PM-014', '2 Nos. Flexible Hose at Jetty & Installation'],
            ['GOB-CAP-PM', 'GOB-PM-015', 'Corrosion Control on Bitumen Pipeline, Painting & Cathodic Protection'],
            ['GOB-CAP-PM', 'GOB-PM-016', 'Isolators for RMU'],
            ['GOB-CAP-PM', 'GOB-PM-017', '40 KVA Generator / Solar System'],

            // ── CAPEX: Motor Vehicles (2) ─────────────────────────────────
            ['GOB-CAP-MV', 'GOB-MV-001', 'Production Vehicle'],
            ['GOB-CAP-MV', 'GOB-MV-002', 'Administration Vehicle'],
        ];

        $gobCodeIds = [];
        foreach ($gobCodes as [$catCode, $code, $name]) {
            $catId = $gobCats[$catCode] ?? null;
            if (! $catId) {
                $this->command->warn("  GOB: category {$catCode} not found — skipping {$code}");
                continue;
            }
            $gobCodeIds[] = $this->upsertCode($code, $name, $catId);
        }

        $this->command->info('  ' . count($gobCodeIds) . ' GOB-specific codes upserted.');

        // ── GEL codes that also cover GOBITUMEN SG&A items ────────────────
        // 17 SG&A lines map directly to existing GEL subsidiary-scoped codes.
        // Assigning them to GOB avoids creating duplicate account codes.
        $gelSharedForGob = [
            '60601000', // Salaries (Administrative)
            '60901000', // Staff Cost – Training
            '62700000', // Subscriptions
            '61101000', // Building / Fire & Burglary Insurance
            '61102000', // Group Personal Accident Insurance
            '61103000', // Vehicle Insurance
            '61109001', // Group Life Insurance – Comprehensive
            '61506000', // Maintenance of Computers & Accessories (Software/Network)
            '61502000', // Maintenance of Administrative Building (Office Maintenance)
            '60500000', // Administration Vehicles Maintenance (Vehicle Maintenance)
            '62401000', // Electricity (Administrative)
            '62800000', // CSR / Donations
            '61508000', // Software License (ERP / Microsoft)
            '60701000', // Board of Directors – Remuneration
            '60702000', // Board of Directors – Other Expenses
            '60611000', // Postage & Telephone
            '61404000', // Professional Services (Non-Audit / Consultancy)
            '62300000', // Printing & Stationery
            '60801000', // Travels (Local Missions & Accommodation)
            '61402000', // Audit Expenses
            '63000000', // Bank Charges (Financial Charges)
        ];

        $gelSharedForGobIds = DB::table('account_codes')
            ->where('scope', 'subsidiary')
            ->whereIn('code', $gelSharedForGob)
            ->pluck('id')
            ->all();

        $missingGob = array_diff(
            $gelSharedForGob,
            DB::table('account_codes')->where('scope', 'subsidiary')->whereIn('code', $gelSharedForGob)->pluck('code')->all()
        );
        foreach ($missingGob as $mc) {
            $this->command->warn("  GOB: GEL shared code {$mc} not found — skipping");
        }

        $this->command->info('  ' . count($gelSharedForGobIds) . ' GEL codes also assigned to GOB (SG&A).');

        // ==================================================================
        // ASSIGN CODES TO GOIL BITUMEN LTD (GOB)
        // ==================================================================
        $gobAllIds = array_values(array_unique(array_merge($gobCodeIds, $gelSharedForGobIds)));
        $this->assignCodes($gobId, $gobAllIds);

        // ── Summary ────────────────────────────────────────────────────────
        $gelCount = DB::table('subsidiary_account_codes')
            ->where('subsidiary_id', $gelId)->count();
        $gulCount = DB::table('subsidiary_account_codes')
            ->where('subsidiary_id', $gulId)->count();
        $gobCount = DB::table('subsidiary_account_codes')
            ->where('subsidiary_id', $gobId)->count();

        $this->command->info('SubsidiarySeeder complete.');
        $this->command->info("  Go Energy Ltd    (GEL): {$gelCount} codes assigned across " . count($gelCats) . ' GoEnergy-specific categories');
        $this->command->info("  GOIL Upstream Ltd (GUL): {$gulCount} codes assigned across " . count($gulCats) . ' GUL-specific categories');
        $this->command->info("  GOIL Bitumen Ltd  (GOB): {$gobCount} codes assigned across " . count($gobCats) . ' GOIL-Bitumen-specific categories');
    }
}
