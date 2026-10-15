<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Creates Go Energy Ltd (GEL) and GOIL Upstream Ltd (GUL) as subsidiaries,
 * assigns existing GOIL account codes by name-match, and creates NEW account
 * codes (prefixed GEL- / GUL-) for items that have no GOIL equivalent.
 *
 * Safe to re-run: uses firstOrCreate-style inserts and replaces code assignments.
 *
 * New codes created can be identified in the system by their GEL- / GUL- prefix.
 */
class SubsidiarySeeder extends Seeder
{
    // ── Helper: get or create an account_code row ──────────────────────────
    private function upsertCode(string $code, string $name, int $categoryId): int
    {
        $existing = DB::table('account_codes')->where('code', $code)->first();
        if ($existing) {
            if ($existing->name !== $name) {
                DB::table('account_codes')->where('id', $existing->id)->update(['name' => $name, 'updated_at' => now()]);
            }
            return $existing->id;
        }

        return DB::table('account_codes')->insertGetId([
            'account_category_id' => $categoryId,
            'code'                => $code,
            'name'                => $name,
            'is_active'           => 1,
            'sort_order'          => 0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    // ── Helper: assign a list of account_code IDs to a subsidiary ─────────
    private function assignCodes(int $subsidiaryId, array $codeIds): void
    {
        // Remove existing assignments and re-insert to stay idempotent
        DB::table('subsidiary_account_codes')
            ->where('subsidiary_id', $subsidiaryId)
            ->delete();

        $rows = [];
        foreach (array_unique($codeIds) as $codeId) {
            $rows[] = [
                'subsidiary_id'   => $subsidiaryId,
                'account_code_id' => $codeId,
                'is_active'       => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        DB::table('subsidiary_account_codes')->insert($rows);
    }

    // ── Main ───────────────────────────────────────────────────────────────
    public function run(): void
    {
        // ── 1. Category ────────────────────────────────────────────────────
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

        // ==================================================================
        // CREATE NEW ACCOUNT CODES
        // ==================================================================
        // Category IDs (from account_categories):
        //  1 Wages & Salaries           2 Travelling & Transport
        //  3 Other Personnel Cost       4 Various Materials
        //  5 Electricity & Water        6 Maintenance & Running Cost
        //  7 Various Services           8 Professional Service
        //  9 Taxes / Levies / Permits  10 Equipment Hiring & Rental
        // 12 Insurance Premium         13 Post & Telecommunication
        // 14 Sales Promotion & Adv.    15 Other Costs
        // 17 Financial Charges         20 Health, Safety & Environment
        // 21 Contingency              22 Capital Expenditure
        // 23 Sales Revenue            24 Cost of Sales

        // ── NEW CODES FOR GEL (Go Energy Ltd) ─────────────────────────────
        // Items in GoEnergy's template that have no GOIL equivalent:

        $gelNew = [
            // From Recurrent Expenses – unmatched items
            'GEL-60120' => ['Materials to Warehouse',              4],
            'GEL-61102' => ['Group Personal Accident Insurance',   12],
            'GEL-61108' => ['Directors Indemnity Insurance',       12],
            'GEL-61607' => ['Commercial Information',             15],
            'GEL-62990' => ['Archiving',                          15],
            'GEL-65108' => ['HSE Programmes',                     20],
            'GEL-69100' => ['Stock Exchange Expenses',            17],
            // From Recurrent Expenses – Laundry (61601000 in template – not Advertising)
            'GEL-61601' => ['Laundry Expenses (Admin)',            3],
            // Terminal Maintenance (listed without code in template)
            'GEL-60500T'=> ['Terminal Maintenance',                6],
            // From Purchases sheet – GoEnergy sells generic wholesale petroleum
            // products (PMS, AGO, ATK etc.) – NOT GOIL's branded retail products.
            // Each product needs its own Sales Revenue and Cost of Sales code.
            // Sales Revenue
            'GEL-REV-PMS'  => ['Gasoline (PMS)',               23],
            'GEL-REV-AGO'  => ['Gasoil (AGO)',                 23],
            'GEL-REV-MGOL' => ['Marine Gasoil (Local)',        23],
            'GEL-REV-MGOF' => ['Marine Gasoil (Rig & Foreign)',23],
            'GEL-REV-AGOX' => ['AGO Export',                   23],
            'GEL-REV-AGON' => ['AGO Mines',                    23],
            'GEL-REV-ATK'  => ['Jet A1 (ATK)',                 23],
            'GEL-REV-HFO'  => ['HFO',                         23],
            'GEL-REV-LPG'  => ['LPGas',                       23],
            // Cost of Sales
            'GEL-COS-PMS'  => ['Gasoline (PMS)',               24],
            'GEL-COS-AGO'  => ['Gasoil (AGO)',                 24],
            'GEL-COS-MGOL' => ['Marine Gasoil (Local)',        24],
            'GEL-COS-MGOF' => ['Marine Gasoil (Rig & Foreign)',24],
            'GEL-COS-AGOX' => ['AGO Export',                   24],
            'GEL-COS-AGON' => ['AGO Mines',                    24],
            'GEL-COS-ATK'  => ['Jet A1 (ATK)',                 24],
            'GEL-COS-HFO'  => ['HFO',                         24],
            'GEL-COS-LPG'  => ['LPGas',                       24],
        ];

        $gelNewIds = [];
        foreach ($gelNew as $code => [$name, $catId]) {
            $gelNewIds[$code] = $this->upsertCode($code, $name, $catId);
        }

        // ── NEW CODES FOR GUL (GOIL Upstream Ltd) ─────────────────────────
        // GUL template uses hierarchical numbers (1, 1.1, 1.1.1) – no account
        // codes. Items are upstream-specific and need new codes.

        $gulNew = [
            // Section 1: Geological & Geophysical (Professional Service cat)
            'GUL-G001' => ['G&G – Seismic Data Acquisition & Processing',     8],
            'GUL-G002' => ['G&G – Special Studies (G&G)',                     8],
            'GUL-G003' => ['G&G – Exploration Operations',                    8],
            'GUL-G004' => ['G&G – Engineering & Reservoir Studies',           8],
            'GUL-G005' => ['G&G – Technical Advisory Services',               8],
            'GUL-G006' => ['G&G – Pre-Well Programme',                        8],

            // Section 1.2: Drilling & Testing
            'GUL-D001' => ['Drilling – Preparation',                         22],
            'GUL-D002' => ['Drilling – Permitting',                           9],
            'GUL-D003' => ['Drilling – Mobilisation & Demobilisation',       22],
            'GUL-D004' => ['Drilling – Well Cost',                           22],
            'GUL-D005' => ['Drilling – Well Test',                           22],

            // Section 1.3: Field Evaluation
            'GUL-F001' => ['Field Evaluation – Future Developments',          8],
            'GUL-F002' => ['Reservoir Development Studies',                   8],

            // Section 1.4: Scientific software (distinct from general IT)
            'GUL-SW001' => ['Scientific Software – G&G Licences & Maintenance', 6],
            'GUL-SW002' => ['Technical Databases',                            6],

            // Section 2.1: Office
            'GUL-OF001' => ['Office Storage',                                 6],
            'GUL-UT001' => ['Utilities – Heating / Other',                    5],

            // Section 2.3: Block Promotion
            'GUL-PR001' => ['Block Promotion – Public Relations',            14],
            'GUL-MK001' => ['Block Promotion – Direct Marketing',            14],
            'GUL-PR002' => ['Stakeholder Engagement',                        14],

            // Section 2.4: CSR
            'GUL-CSR001'=> ['CSR – Community Healthcare & Education Support', 15],
            'GUL-CSR002'=> ['CSR – Corporate Gifts',                          15],

            // Section 2.5: Travel
            'GUL-TR001' => ['Travel – Visa Costs',                            9],

            // Section 2.6: HR
            'GUL-HR001' => ['Recruitment',                                    3],

            // Section 3: PA Obligations (Petroleum Agreement)
            'GUL-PA001' => ['PA Obligation – Annual Surface Rentals',         9],
            'GUL-PA002' => ['PA Obligation – Annual Training Fee',            9],
            'GUL-PA003' => ['PA Obligation – PC Permit',                      9],

            // Section 2.11: Indirect Costs
            'GUL-IND001'=> ['Indirect Costs – Overhead',                     21],
        ];

        $gulNewIds = [];
        foreach ($gulNew as $code => [$name, $catId]) {
            $gulNewIds[$code] = $this->upsertCode($code, $name, $catId);
        }

        // ==================================================================
        // ASSIGN CODES TO GO ENERGY LTD (GEL)
        // ==================================================================
        // Existing GOIL codes mapped by name-similarity from GEL's template
        // (Recurrent Expenses + Capital + Sales/Cost of Sales sheets):
        //
        // ── RECURRENT EXPENSES ──
        // PERSONNEL / SALARIES
        //   60601000  Normal Salaries             → 1  (62608 Normal Salaries & Wages)
        //   60600000  Social Security 13%         → 3  (62610 SSF Employer's Contribution)
        //   60610007  10% PF                      → 4  (62603 Employer Tier 3 Contribution)
        //   60610000  Annual Bonus                → 6  (62601 Annual Bonus)
        //   60610006  13th Mth Salary             → 7  (62604 Staff Cost – Month 13)
        //   60601003  Other Allowances            → 18 (62616 Staff Cost – Misc.)
        //   6061000   End of Year Activities      → 13 (62606 Seasonal End of Year)
        //   *60607000 Medical Expenses            → 14 (62607 Medical)
        //   60608000  Staff Welfare               → 15 (62611 Staff Welfare)
        //   60609001  Fitness & Recreation        → 23 (62724 Fitness & Recreational)
        //   60609000  Uniforms                    → 17 (62615 Uniforms)
        //   60612000  Subsidised Meals            → 22 (62723 Canteen)
        //   60611000  Courier & Dispatch/Tel      → 117 (62772 Postage & Telephone)
        //   61601000  Laundry Expenses            → GEL-61601 (new)
        // TRAINING / TRAVEL
        //   60901000  Staff Training Local        → 16 (62613 Training – Local)
        //   60804000  International Travels       → 11 (62779 Training Overseas)
        //   60801000  Travelling & Mission        → 10 (62778 Transport Expenses)
        // VEHICLES
        //   60500000  Vehicle Maintenance         → 55 (62101 Admin Vehicles Labour/Spares)
        //   60500005  Vehicle Fuel                → 58 (62104 Admin Vehicles Fuels)
        //   60500004  Lubes consumed              → 59 (62106 Admin Vehicles Lubes)
        //   61604000  Vehicle Licence/Rd.Worthy   → 95 (62107 Admin Vehicles Permits)
        //   61604000FINHOF EPA/NPA Permits        → 97 (62771 Permit & Licenses)
        //   61103000  Vehicle Insurance           → 108 (62105 Admin Vehicles Insurance)
        // OFFICE / FACILITIES
        //   61502000  Office Maintenance          → 62 (62740 Maintenance of Office)
        //   61501000  Office Equipment Mtce       → 68 (62760 Maint. Office Equipment)
        //   61503000  Executive House Mtce        → 65 (62752 Maint. Residential)
        //   61506000  Computer/Software Mtce      → 60 (62201 Computing – Network)
        //   61508000  Software License            → 70 (62777 Software License)
        //   62310000  Computer Forms              → 27 (62202 Computing – Forms)
        //   62300000  Stationery & Printing       → 28 (62773 Printing & Stationery)
        //   65300000  Head Office Rent            → 81 (61610 Rent – Administration)
        //   65300001  Building Rents              → (same 81, deduplicated)
        //   65202002  Property Rates              → 98 (62775 Property Rate)
        //   61603000  Equipment Hiring            → 99 (61605 Equipment Hiring)
        //   61504000  Janitorial/Cleaning         → 84 (62776 Cleaning Services)
        //   65200000  Security                    → 82 (62502 Security Services)
        //   60606000  Casual Labour               → 83 (62703 Contract Labour)
        //   [no code] Terminal Maintenance        → GEL-60500T (new)
        // UTILITIES
        //   62401000  Electricity – Offices       → 31 (62722 Electricity Offices & Houses)
        //   62402000  Electricity – Houses        → (same 31, deduplicated)
        //   65102001  Electricity – Depots        → 29 (61604 Electricity for Depots)
        //   62501000  Water – Offices             → 32 (62780 Water Offices & Houses)
        //   62502000  Water – Houses              → (same 32, deduplicated)
        //   65102002  Water – Depots              → 30 (61806 Water for Depots)
        // INSURANCE
        //   61101000  Fire & Burglary             → 102 (62407 Assets All Risk)
        //   61106000  Product at Depots (insur.)  → (same 102, deduplicated)
        //   61103000  Vehicle Insurance           → 108 (62105)
        //   61109001  Group Life Insurance        → 115 (62605 Life Insurance)
        //   61102000  Group Personal Accident     → GEL-61102 (new)
        //   61108000  Directors Indemnity         → GEL-61108 (new)
        // PROFESSIONAL SERVICES
        //   61402000  Auditors Fees               → 89 (62702 Audit Fees)
        //   61401000  Legal Expenses              → 92 (62774 Professional Services)
        //   61404000  Professional Services       → (same 92, deduplicated)
        //   61408000  Standard Authority Fees     → 86 (61802 Standard Boards & Statutory)
        //   651030    Third Party Survey          → 87 (61804 Survey Fees)
        // DIRECTORS / GOVERNANCE
        //   60701000  Directors Remuneration      → 90 (62711 Directors Remuneration)
        //   60702000  Directors Other Expenses    → 91 (62712 Directors Expenses)
        //   69200000  Annual General Meeting      → 88 (62701 Annual General Meeting)
        //   69100000  Stock Exchange Expenses     → GEL-69100 (new)
        // SALES & MARKETING
        //   61301000  Advertising                 → 118 (61601 Advertising)
        //   61302000  Sales & Business Promo      → 119 (61801 Sales Promotion)
        //   61303000  Seasonal Promotions         → 121 (61813 Business Promotion)
        //   62100000  Charity / Sponsorship       → 120 (62728 Sponsorship)
        // OTHER
        //   62700000  Subscriptions               → 124 (61803 Subscriptions)
        //   62800000  Donations                   → 127 (62721 Donations)
        //   62900000  Entertainment               → 129 (62727 Business Lunch)
        //   61607000  Commercial Information      → GEL-61607 (new)
        //   62990000  Archiving                   → GEL-62990 (new)
        //   65108000  HSE Programmes              → GEL-65108 (new)
        //   60120001  Materials to Warehouse      → GEL-60120 (new)
        //   61800000  Exchange Loss/Inv.Losses    → 144 (63103 Exchange Losses)
        //                                       + 75 (62781 Stock Losses)
        //   63000000  Financial Charges           → 141 (63000 Finance Cost)
        // ── CAPITAL EXPENDITURE SHEET ──
        // Mapped to existing GOIL CAPEX categories:
        //   Construction Works          → 152 (11200 Building)
        //   Terminal & Operational Equip→ 153 (11300 Plant, Machinery & Equipment)
        //   Motor Vehicles              → 154 (11400 Motor Vehicle)
        //   Computers (hardware)        → 157 (11700 Computers & Accessories)
        //   Software                    → 156 (11600 Software)
        //   HSSE Equipment              → 153 (11300, deduplicated)
        // ── PURCHASES / SALES SHEET (Products) ──
        // GoEnergy sells wholesale petroleum products – generic grades, NOT
        // GOIL's branded retail products (Super XP, Diesel XP, etc.).
        // All 9 products get dedicated GEL-REV-xxx / GEL-COS-xxx codes:
        //   GASOLINE (PMS)              → GEL-REV-PMS / GEL-COS-PMS
        //   GASOIL (AGO)                → GEL-REV-AGO / GEL-COS-AGO
        //   MARINE GASOIL-LOCAL         → GEL-REV-MGOL / GEL-COS-MGOL
        //   MARINE GASOIL-RIG/FOREIGN   → GEL-REV-MGOF / GEL-COS-MGOF
        //   AGO EXPORT                  → GEL-REV-AGOX / GEL-COS-AGOX
        //   AGO MINES                   → GEL-REV-AGON / GEL-COS-AGON
        //   JET A1 (ATK)                → GEL-REV-ATK  / GEL-COS-ATK
        //   HFO                         → GEL-REV-HFO  / GEL-COS-HFO
        //   LPGas                       → GEL-REV-LPG  / GEL-COS-LPG

        $gelExisting = [
            // Personnel
            1, 3, 4, 6, 7, 18, 13, 14, 15, 23, 17, 22, 117,
            // Training / Travel
            16, 11, 10,
            // Admin Vehicles
            55, 58, 59, 95, 97, 108,
            // Office / Facilities
            62, 68, 65, 60, 70, 27, 28, 81, 98, 99, 84, 82, 83,
            // Utilities
            31, 29, 32, 30,
            // Insurance
            102, 115,
            // Professional Services
            89, 92, 86, 87,
            // Directors / Governance
            90, 91, 88,
            // Sales / Marketing
            118, 119, 121, 120,
            // Other
            124, 127, 129, 144, 75, 141,
            // Capital Expenditure
            152, 153, 154, 155, 156, 157,
            // NOTE: Sales Revenue and Cost of Sales are covered by GEL-REV-xxx
            // and GEL-COS-xxx new codes (GoEnergy's wholesale products differ
            // from GOIL's branded retail products – not mapped to GOIL 41000/52000)
        ];

        $gelAllIds = array_merge(
            $gelExisting,
            array_values($gelNewIds)
        );

        $this->assignCodes($gelId, $gelAllIds);

        // ==================================================================
        // ASSIGN CODES TO GOIL UPSTREAM LTD (GUL)
        // ==================================================================
        // All items in GUL template (hierarchical 1, 1.1, 1.2... numbering).
        // Existing GOIL codes used where names match; GUL-xxx new codes for
        // upstream-specific items.
        //
        // ── SECTION 1: GEOLOGICAL & GEOPHYSICAL ──
        //   1.1.1  Seismic Data Acquisition       → GUL-G001 (new)
        //   1.1.2  Special Studies                → GUL-G002 (new)
        //   1.1.3  Exploration Operations         → GUL-G003 (new)
        //   1.1.4  Engineering Studies            → GUL-G004 (new)
        //   1.1.7  G&G Technical Advisory         → GUL-G005 (new)
        //   1.1.9  Pre-Well Programme             → GUL-G006 (new)
        //   1.2.1  Drilling Preparation           → GUL-D001 (new)
        //   1.2.2  Drilling Permitting            → GUL-D002 (new)
        //   1.2.3  Mobilisation & Demob           → GUL-D003 (new)
        //   1.2.4  Well Cost                      → GUL-D004 (new)
        //   1.2.5  Well Test                      → GUL-D005 (new)
        //   1.3.1  Field Evaluation               → GUL-F001 (new)
        //   1.3.2  Reservoir Development          → GUL-F002 (new)
        //   1.4.1  Scientific Software Purchase   → GUL-SW001 (new)
        //   1.4.2  Hardware Purchase              → 157 (11700 Computers)
        //   1.4.3  Current Maintenance            → 60 (62201 Computing Maintenance)
        //
        // ── SECTION 2.1: OFFICE COSTS ──
        //   2.1.1.1  Office Rent                  → 81 (61610 Rent Administration)
        //   2.1.1.2  Office Rates                 → 98 (62775 Property Rate)
        //   2.1.1.5  Office Other costs           → 128 (62999 Admin Expenses Others)
        //   2.1.2.1  Office Storage               → GUL-OF001 (new)
        //   2.1.2.2  Catering Services            → 22 (62723 Canteen)
        //   2.1.2.3  Cleaning                     → 84 (62776 Cleaning Services)
        //   2.1.2.4  General Administration       → 128 (62999)
        //   2.1.2.5  Electricity                  → 31 (62722 Electricity)
        //   2.1.2.6  Water                        → 32 (62780 Water)
        //   2.1.2.7  Heating                      → GUL-UT001 (new)
        //   2.1.2.8  Fuel Internal Consumption    → 58 (62104 Admin Vehicles Fuels)
        //   2.1.2.9  Office Service/Prop Mgt      → 62 (62740 Maintenance of Office)
        //   2.1.2.12 Licences and Permits         → 97 (62771 Permit & Licenses)
        //   2.1.2.14 Office Security              → 82 (62502 Security Services)
        //   2.1.3.1  Stationery/Office Supplies   → 28 (62773 Printing & Stationery)
        //   2.1.3.2  Furniture & Equipment        → 155 (11500 Furniture & Fittings) capex
        //   2.1.3.3  Equipment Rentals            → 99 (61605 Equipment Hiring)
        //   2.1.3.4  Office Repairs/Maintenance   → 62 (62740)
        //   2.1.3.5  Residential Repairs          → 65 (62752 Maint. Residential)
        //   2.1.4.x  Computer Expenses            → 60 (62201) + 70 (62777) + 157 (11700)
        //   2.1.5.1-3 Tel/Post/Courier            → 117 (62772 Postage & Telephone)
        //   2.1.5.5  Mobile Telephones            → 117 (same)
        //   2.1.5.6  Internet                     → 116 (62203 WAN & Internet)
        //
        // ── SECTION 2.2: STAFF COSTS ──
        //   All staff positions                   → 1  (62608 Normal Salaries & Wages)
        //   SSF Contribution                      → 3  (62610 SSF)
        //   Tier 3 Pension                        → 4  (62603 Employer Tier 3)
        //   Annual Bonus                          → 6  (62601 Annual Bonus)
        //   13th Month                            → 7  (62604 Month 13)
        //   Support Staff (Contract Labour)       → 83 (62703 Contract Labour)
        //
        // ── SECTION 2.3: BLOCK PROMOTION ──
        //   Consultants                           → 92 (62774 Professional Services)
        //   Public Relations                      → GUL-PR001 (new)
        //   Advertisement                         → 118 (61601 Advertising)
        //   Direct Marketing                      → GUL-MK001 (new)
        //   Stakeholder Engagement                → GUL-PR002 (new)
        //   Business Promotion                    → 121 (61813 Business Promotion)
        //
        // ── SECTION 2.4: CORPORATE SOCIAL RESPONSIBILITY ──
        //   Healthcare, Education, Community      → GUL-CSR001 (new)
        //   Sponsorships                          → 120 (62728 Sponsorship)
        //   Donations                             → 127 (62721 Donations)
        //   CSI / General CSR                     → 125 (62704 CSI)
        //   Corporate Gifts                       → GUL-CSR002 (new)
        //
        // ── SECTION 2.5: TRAVEL & ENTERTAINMENT ──
        //   Car Hire / Vehicle Maintenance        → 55 (62101 Admin Vehicles Labour/Spares)
        //   Flights (local)                       → 10 (62778 Transport Expenses)
        //   Hotels & Subsistence                  → 24 (62725 Hotel & Outstation)
        //   Flights (overseas)                    → 11 (62779 Training Overseas)
        //   Travel Visa Costs                     → GUL-TR001 (new)
        //   Entertainment / Staff Social          → 129 (62727 Business Lunch)
        //   Seasonal Activities                   → 13 (62606 Seasonal End of Year)
        //   Vehicle fuel                          → 58 (62104)
        //
        // ── SECTION 2.6: TRAINING ──
        //   Training Local/Overseas               → 16 (62613) + 11 (62779)
        //   Recruitment                           → GUL-HR001 (new)
        //   Conferences & Seminars                → 16 (62613)
        //
        // ── SECTION 2.7: SUBSCRIPTIONS ──
        //   Subscriptions & Memberships           → 124 (61803 Subscriptions)
        //   Professional Memberships              → 8  (62612 Subs. to Prof. Bodies)
        //   Technical Databases                   → GUL-SW002 (new)
        //   Charitable Donations                  → 127 (62721)
        //
        // ── SECTION 2.8: INSURANCE ──
        //   General / All Risks Insurance         → 102 (62407 Assets All Risk)
        //   Motor Vehicle Insurance               → 108 (62105 Admin Vehicles Insurance)
        //   Personal Accident / Medical           → 14 (62607 Medical)
        //
        // ── SECTION 2.9: PROFESSIONAL FEES ──
        //   Audit Fees                            → 89  (62702 Audit Fees)
        //   Tax / Consultancy Fees                → 92  (62774 Professional Services)
        //   Legal Fees                            → 92  (same)
        //   Board of Directors Fees               → 126 (62710 Directors Fees & Expenses)
        //
        // ── SECTION 2.10: FINANCIAL CHARGES ──
        //   Bank Charges / Credit Card Fees       → 142 (63101 Bank Charges)
        //   Finance / Lease Finance Charge        → 141 (63000 Finance Cost)
        //   Stamp Duties                          → 97  (62771 Permit & Licenses)
        //
        // ── SECTION 2.11: INDIRECT COSTS ──
        //   Overhead                              → GUL-IND001 (new)
        //
        // ── SECTION 3: PA OBLIGATIONS ──
        //   Annual Surface Rentals                → GUL-PA001 (new)
        //   Annual Training Fee                   → GUL-PA002 (new)
        //   PC Permit                             → GUL-PA003 (new)
        //
        // ── CAPITAL EXPENDITURE (upstream) ──
        //   Software Purchase                     → 156 (11600 Software)
        //   Hardware Purchase                     → 157 (11700 Computers & Accessories)
        //   Operational Equipment (drilling etc.) → 153 (11300 Plant, Machinery & Equip)
        //   Motor Vehicles                        → 154 (11400 Motor Vehicle)
        //   Right of Use Assets (leases)          → 158 (11800 Right of Use Assets)
        //   Capital Work in Progress              → 159 (11900 CWIP)

        $gulExisting = [
            // Personnel / Salaries
            1, 3, 4, 6, 7, 83,
            // Travel & Subsistence
            10, 11, 24, 55, 58, 13,
            // Training
            16,
            // Office
            81, 98, 128, 31, 32, 62, 68, 84, 82, 97, 28, 99, 65, 22,
            // Communications
            117, 116,
            // Computing
            60, 70, 27,
            // Insurance
            102, 108, 14, 115,
            // Professional Fees / Governance
            89, 92, 126, 88, 8,
            // Financial
            141, 142, 144,
            // Marketing / CSR
            118, 119, 120, 121, 124, 125, 127, 129,
            // Other Personnel
            15, 17, 23,
            // Staff-related
            18,
            // Capital Expenditure
            152, 153, 154, 155, 156, 157, 158, 159,
        ];

        $gulAllIds = array_merge(
            $gulExisting,
            array_values($gulNewIds)
        );

        $this->assignCodes($gulId, $gulAllIds);

        // ── Output ─────────────────────────────────────────────────────────
        $gelCount = DB::table('subsidiary_account_codes')
            ->where('subsidiary_id', $gelId)->count();
        $gulCount = DB::table('subsidiary_account_codes')
            ->where('subsidiary_id', $gulId)->count();

        $this->command->info("SubsidiarySeeder complete.");
        $this->command->info("  Go Energy Ltd (GEL): {$gelCount} codes assigned  (" . count($gelNewIds) . " new GEL- codes created)");
        $this->command->info("  GOIL Upstream Ltd (GUL): {$gulCount} codes assigned  (" . count($gulNewIds) . " new GUL- codes created)");
        $this->command->line('');
        $this->command->line('  New GEL- codes:');
        foreach ($gelNew as $code => $item) {
            $this->command->line("    {$code}  {$item[0]}");
        }
        $this->command->line('');
        $this->command->line('  New GUL- codes:');
        foreach ($gulNew as $code => $item) {
            $this->command->line("    {$code}  {$item[0]}");
        }
    }
}
