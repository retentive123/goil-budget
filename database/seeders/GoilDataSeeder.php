<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Loads GOIL's departments, account categories, and account codes
 * from the GOIL_Account_Codes_Organised_1.xlsx source file.
 *
 * Run: php artisan db:seed --class=GoilDataSeeder
 *
 * Safe to re-run — uses updateOrCreate / firstOrCreate throughout.
 * All codes are assigned to every department; remove unused ones
 * per-department via Admin → Departments → Account Codes.
 */
class GoilDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Departments ────────────────────────────────────────────────────
        $this->command->info('Seeding departments…');

        $departments = [
            ['name' => 'GCEO/MD',             'code' => '01001', 'description' => 'Office of the Group Chief Executive Officer & Managing Director. Provides overall strategic leadership and corporate governance.'],
            ['name' => 'COO',                  'code' => '15000', 'description' => 'Office of the Chief Operating Officer. Oversees operational efficiency and coordination across all business units.'],
            ['name' => 'Finance',              'code' => '05001', 'description' => 'Financial planning, accounting, treasury, and management reporting for the company.'],
            ['name' => 'Admin/HR',             'code' => '07000', 'description' => 'Administration & Human Resources. Manages human capital, staff welfare, payroll, and general administrative services.'],
            ['name' => 'Operations',           'code' => '02001', 'description' => 'Manages day-to-day depot, station, and distribution operations including fuel storage and logistics.'],
            ['name' => 'CIA',                  'code' => '01002', 'description' => 'Corporate Internal Audit. Provides independent assurance on risk management, internal controls, and governance processes.'],
            ['name' => 'HSSE',                 'code' => '01003', 'description' => 'Health, Safety, Security & Environment. Ensures compliance with HSSE standards and policies across all operations.'],
            ['name' => 'TSPM',                 'code' => '03001', 'description' => 'Technical Services & Project Management. Oversees engineering projects, technical infrastructure, and asset maintenance.'],
            ['name' => 'Estates',              'code' => '18000', 'description' => 'Manages company-owned properties, buildings, land leases, and residential accommodation.'],
            ['name' => 'Legal',                'code' => '01004', 'description' => 'Legal Affairs. Provides legal advisory services, contract management, and regulatory compliance oversight.'],
            ['name' => 'Research',             'code' => '01005', 'description' => 'Research & Development. Conducts market research, competitive analysis, and product development studies.'],
            ['name' => 'TSP',                  'code' => '16000', 'description' => 'Transport Services & Procurement. Manages the company\'s vehicle fleet, transport logistics, and related procurement.'],
            ['name' => 'Non Fuels',            'code' => '17000', 'description' => 'Manages non-fuel product lines including lubricants, LPG, GoCard, and other retail offerings.'],
            ['name' => 'Procurement',          'code' => '01007', 'description' => 'Manages sourcing, purchasing, and supply chain activities for all goods and services across the company.'],
            ['name' => 'Fuels Marketing',      'code' => '04001', 'description' => 'Manages marketing and sales of petroleum products including petrol, diesel, kerosene, LPG, and aviation fuel.'],
            ['name' => 'IT',                   'code' => '06001', 'description' => 'Information Technology. Manages ICT infrastructure, enterprise systems, and digital transformation initiatives.'],
            ['name' => 'Business Development', 'code' => '01006', 'description' => 'Identifies and develops new business opportunities, strategic partnerships, and market expansion initiatives.'],
            ['name' => 'Corporate Affairs',    'code' => '08001', 'description' => 'Public relations, stakeholder engagement, corporate communications, and CSR initiatives.'],
            ['name' => 'Risk',                 'code' => '01008', 'description' => 'Enterprise Risk Management. Identifies, assesses, and mitigates operational, financial, and strategic risks.'],
        ];

        $deptIds = [];
        foreach ($departments as $dept) {
            $row = DB::table('departments')->updateOrInsert(
                ['code' => $dept['code']],
                [
                    'name'        => $dept['name'],
                    'code'        => $dept['code'],
                    'description' => $dept['description'] ?? null,
                    'budget_type' => 'expense',
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
            $deptIds[] = DB::table('departments')->where('code', $dept['code'])->value('id');
        }
        $deptIds = array_filter($deptIds); // remove nulls

        $this->command->info('  ' . count($departments) . ' departments done.');

        // ── 2. Account Categories ─────────────────────────────────────────────
        $this->command->info('Seeding account categories…');

        // Format: [code, name]
        $categories = [
            ['CAT-01', 'Wages & Salaries'],
            ['CAT-02', 'Travelling & Transport'],
            ['CAT-03', 'Other Personnel Cost'],
            ['CAT-04', 'Various Materials'],
            ['CAT-05', 'Electricity & Water'],
            ['CAT-06', 'Maintenance & Running Cost'],
            ['CAT-07', 'Various Services'],
            ['CAT-09', 'Professional Service'],
            ['CAT-10', 'Taxes / Levies / Permits'],
            ['CAT-11', 'Equipment Hiring & Rental'],
            ['CAT-12', 'Stations Land Leasing'],
            ['CAT-13', 'Insurance Premium'],
            ['CAT-14', 'Post & Telecommunication'],
            ['CAT-15', 'Sales Promotion & Advertising'],
            ['CAT-16', 'Other Costs'],
            ['CAT-17', 'Depreciation of Fixed Assets'],
            ['CAT-18', 'Financial Charges'],
            ['CAT-19', 'Sundry Charges'],
            ['CAT-20', 'Discount'],
            ['CAT-21', 'Health, Safety, Security & Environment'],
            ['CAT-99', 'Contingency'],
        ];

        $catMap = []; // name → id
        foreach ($categories as [$catCode, $catName]) {
            DB::table('account_categories')->updateOrInsert(
                ['code' => $catCode],
                [
                    'name'        => $catName,
                    'code'        => $catCode,
                    'budget_type' => 'expense',
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
            $catMap[$catName] = DB::table('account_categories')->where('code', $catCode)->value('id');
        }

        // CAPEX category
        DB::table('account_categories')->updateOrInsert(
            ['code' => 'CAT-CAPEX'],
            [
                'name'        => 'Capital Expenditure',
                'code'        => 'CAT-CAPEX',
                'budget_type' => 'capital_expenditure',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
        $catMap['Capital Expenditure'] = DB::table('account_categories')->where('code', 'CAT-CAPEX')->value('id');

        // Sales Revenue category
        DB::table('account_categories')->updateOrInsert(
            ['code' => 'CAT-REV'],
            [
                'name'        => 'Sales Revenue',
                'code'        => 'CAT-REV',
                'budget_type' => 'revenue',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
        $catMap['Sales Revenue'] = DB::table('account_categories')->where('code', 'CAT-REV')->value('id');

        // Cost of Sales category
        DB::table('account_categories')->updateOrInsert(
            ['code' => 'CAT-COS'],
            [
                'name'        => 'Cost of Sales',
                'code'        => 'CAT-COS',
                'budget_type' => 'expense',
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
        $catMap['Cost of Sales'] = DB::table('account_categories')->where('code', 'CAT-COS')->value('id');

        $this->command->info('  ' . (count($categories) + 3) . ' categories done.');

        // ── 3. Account Codes ──────────────────────────────────────────────────
        $this->command->info('Seeding account codes…');

        // Format: [category_name, code, name]
        $codes = [
            // Wages & Salaries
            ['Wages & Salaries', '62608', 'Normal Salaries & Wages'],
            ['Wages & Salaries', '62609', 'Overtime'],
            ['Wages & Salaries', '62610', "SSF Employer's Contribution"],
            ['Wages & Salaries', '62603', 'Employer Tier 3 Contribution'],
            ['Wages & Salaries', '62602', 'Early Retirement'],
            ['Wages & Salaries', '62601', 'Annual Bonus'],
            ['Wages & Salaries', '62604', 'Staff Cost – Month 13'],
            ['Wages & Salaries', '62612', 'Subscriptions to Professional Bodies'],

            // Travelling & Transport
            ['Travelling & Transport', '62614', 'Transfers & Relocation Allowances'],
            ['Travelling & Transport', '62778', 'Transport Expenses'],
            ['Travelling & Transport', '62779', 'Training (Overseas)'],

            // Other Personnel Cost
            ['Other Personnel Cost', '61805', 'Training – Frontline'],
            ['Other Personnel Cost', '62618', 'Seasonal, End of Year Activities'],
            ['Other Personnel Cost', '62607', 'Medical'],
            ['Other Personnel Cost', '62611', 'Staff Welfare'],
            ['Other Personnel Cost', '62613', 'Training – Local'],
            ['Other Personnel Cost', '62615', 'Uniforms'],
            ['Other Personnel Cost', '62616', 'Staff Cost – Misc.'],
            ['Other Personnel Cost', '62617', 'Staff Cost – Retired Staff Medicals'],
            ['Other Personnel Cost', '62619', 'Scholarship'],
            ['Other Personnel Cost', '62620', 'Staff Cost – Medical Screening'],
            ['Other Personnel Cost', '62723', 'Canteen'],
            ['Other Personnel Cost', '62724', 'Fitness & Recreational Assistance'],
            ['Other Personnel Cost', '62725', 'Hotel & Outstation Expenses'],

            // Various Materials
            ['Various Materials', '61603', 'Coupon Production'],
            ['Various Materials', '61607', 'POS / GoCard Expenses'],
            ['Various Materials', '62202', 'Computing Expenses – Forms & Accessories'],
            ['Various Materials', '62773', 'Printing & Stationery'],

            // Electricity & Water
            ['Electricity & Water', '61604', 'Electricity for Depots'],
            ['Electricity & Water', '61806', 'Water for Depots'],
            ['Electricity & Water', '62722', 'Electricity (Offices & Houses)'],
            ['Electricity & Water', '62780', 'Water (Offices & Houses)'],

            // Maintenance & Running Cost
            ['Maintenance & Running Cost', '61400', 'Maintenance of Stations'],
            ['Maintenance & Running Cost', '61411', 'Genset Maint. & Repairs'],
            ['Maintenance & Running Cost', '61412', 'Pump Maint. & Repairs'],
            ['Maintenance & Running Cost', '61413', 'General Maint. & Repairs'],
            ['Maintenance & Running Cost', '61420', 'Maintenance of Consumer Installations'],
            ['Maintenance & Running Cost', '61431', 'Maint. of Aviation Depot – Accra'],
            ['Maintenance & Running Cost', '61432', 'Maint. of Aviation Depot – Kumasi'],
            ['Maintenance & Running Cost', '61433', 'Maint. of Aviation Depot – Takoradi'],
            ['Maintenance & Running Cost', '61445', 'Maint. of Aviation Depot – Tamale'],
            ['Maintenance & Running Cost', '61434', 'Maint. of LPG & Lubes Depot – Kumasi'],
            ['Maintenance & Running Cost', '61435', 'Maint. of LPG & Lubes Depot – Tema'],
            ['Maintenance & Running Cost', '61436', 'Maint. of Bunkering – Sekondi Naval Base & T\'adi Harbour'],
            ['Maintenance & Running Cost', '61437', 'Maint. of Lubes Depot – Takoradi'],
            ['Maintenance & Running Cost', '61438', 'Maint. of Lubes Depot – Tamale'],
            ['Maintenance & Running Cost', '61439', 'Maint. of Installations'],
            ['Maintenance & Running Cost', '61450', 'Maintenance of Plant, Machinery & Equipment'],
            ['Maintenance & Running Cost', '61501', 'Revenue Generating Vehicles – Labour Charges'],
            ['Maintenance & Running Cost', '61502', 'Revenue Generating Vehicles – Spares'],
            ['Maintenance & Running Cost', '61503', 'Revenue Generating Vehicles – Tyres'],
            ['Maintenance & Running Cost', '61504', 'Revenue Generating Vehicles – Fuels'],
            ['Maintenance & Running Cost', '61506', 'Revenue Generating Vehicles – Lubes'],
            ['Maintenance & Running Cost', '61507', 'Revenue Generating Vehicles – Permits & Licensing'],
            ['Maintenance & Running Cost', '62101', 'Administration Vehicles – Labour Charges & Spares'],
            ['Maintenance & Running Cost', '62102', 'Administration Vehicles – Spares'],
            ['Maintenance & Running Cost', '62103', 'Administration Vehicles – Tyres'],
            ['Maintenance & Running Cost', '62104', 'Administration Vehicles – Fuels (Internal Consumption)'],
            ['Maintenance & Running Cost', '62106', 'Administration Vehicles – Lubes'],
            ['Maintenance & Running Cost', '62201', 'Computing Expenses – Computer, Software & Network Maintenance'],
            ['Maintenance & Running Cost', '62501', 'Security – HSSE Maintenance'],
            ['Maintenance & Running Cost', '62740', 'Maintenance of Office'],
            ['Maintenance & Running Cost', '62741', 'Maintenance of Gensets – Offices'],
            ['Maintenance & Running Cost', '62751', 'Maint. of Guest House'],
            ['Maintenance & Running Cost', '62752', 'Maint. of Residential Accommodation'],
            ['Maintenance & Running Cost', '62753', 'Maint. of Head Office'],
            ['Maintenance & Running Cost', '62754', 'Maint. – Other Residences'],
            ['Maintenance & Running Cost', '62760', 'Maintenance of Office Equipment'],
            ['Maintenance & Running Cost', '62761', 'Coupons Replacement'],
            ['Maintenance & Running Cost', '62777', 'Software License'],
            ['Maintenance & Running Cost', '61809', 'Aro Warehouse Maintenance'],
            ['Maintenance & Running Cost', '61810', 'Take Over Stations (Commissions)'],
            ['Maintenance & Running Cost', '61811', 'Lubes Production / Business Development'],
            ['Maintenance & Running Cost', '61812', 'Clearing & Handling Charges'],
            ['Maintenance & Running Cost', '62781', 'Stock Losses'],
            ['Maintenance & Running Cost', '62782', 'Damaged Stocks'],
            ['Maintenance & Running Cost', '62783', 'Debt Recovery'],
            ['Maintenance & Running Cost', '62784', 'Loyalty Award'],
            ['Maintenance & Running Cost', '62785', 'Housing Rent for Personnel'],
            ['Maintenance & Running Cost', '61440', 'Furniture and Office Equipment'],
            ['Maintenance & Running Cost', '61610', 'Rent – Administration'],

            // Various Services
            ['Various Services', '62502', 'Security Services'],
            ['Various Services', '62703', 'Contract Labour'],
            ['Various Services', '62776', 'Cleaning Services'],

            // Professional Service
            ['Professional Service', '61101', 'Calibration of U/G Tanks'],
            ['Professional Service', '61802', 'Standard Boards & Statutory'],
            ['Professional Service', '61804', 'Survey Fees'],
            ['Professional Service', '62701', 'Annual General Meeting'],
            ['Professional Service', '62702', 'Audit Fees'],
            ['Professional Service', '62711', 'Directors Remuneration'],
            ['Professional Service', '62712', 'Directors Expenses'],
            ['Professional Service', '62774', 'Professional Services (Fees & Charges)'],

            // Taxes / Levies / Permits
            ['Taxes / Levies / Permits', '61608', 'Ground Rent & Parking Lot'],
            ['Taxes / Levies / Permits', '61609', 'Motorway Toll – Revenue Generating Vehicles'],
            ['Taxes / Levies / Permits', '62107', 'Administration Vehicles – Permits & Licenses'],
            ['Taxes / Levies / Permits', '62108', 'Administration Vehicles – Motorway Tolls'],
            ['Taxes / Levies / Permits', '62771', 'Permit & Licenses'],
            ['Taxes / Levies / Permits', '62775', 'Property Rate'],

            // Equipment Hiring & Rental
            ['Equipment Hiring & Rental', '61605', 'Equipment Hiring'],
            ['Equipment Hiring & Rental', '61606', 'General Rates on Buildings – Depots & Stations'],

            // Stations Land Leasing
            ['Stations Land Leasing', '61602', 'Annual Lease'],

            // Insurance Premium
            ['Insurance Premium', '62407', 'Assets All Risk'],
            ['Insurance Premium', '61301', 'Insurance – Depots'],
            ['Insurance Premium', '61302', 'Insurance – Motor Vehicles'],
            ['Insurance Premium', '61303', 'Insurance – Plant, Machinery & Equipment (JV)'],
            ['Insurance Premium', '61304', 'Insurance – Stations (Company Owned)'],
            ['Insurance Premium', '61505', 'Revenue Generating Vehicles – Insurance'],
            ['Insurance Premium', '62105', 'Administration Vehicles – Insurance'],
            ['Insurance Premium', '62401', 'Insurance – Head Office'],
            ['Insurance Premium', '62402', 'Insurance – Residences'],
            ['Insurance Premium', '62403', 'Insurance – Goods in Transit'],
            ['Insurance Premium', '62404', 'Insurance – Fidelity Guarantee'],
            ['Insurance Premium', '62405', 'Insurance – Public Liability (KIA, TDI and KSI)'],
            ['Insurance Premium', '62406', 'Insurance – Burglary'],
            ['Insurance Premium', '62605', 'Staff Cost – Life Insurance'],

            // Post & Telecommunication
            ['Post & Telecommunication', '62203', 'Computing Expenses – WAN & Internet'],
            ['Post & Telecommunication', '62772', 'Postage & Telephone'],

            // Sales Promotion & Advertising
            ['Sales Promotion & Advertising', '61601', 'Advertising'],
            ['Sales Promotion & Advertising', '61801', 'Sales Promotion'],
            ['Sales Promotion & Advertising', '62728', 'Sponsorship'],
            ['Sales Promotion & Advertising', '61813', 'Business Promotion (Brand Ambassador)'],
            ['Sales Promotion & Advertising', '61814', 'Branded Items, Events'],
            ['Sales Promotion & Advertising', '61815', 'Bonus – Frontline'],

            // Other Costs
            ['Other Costs', '61803', 'Subscriptions – Various Business Associations'],
            ['Other Costs', '62704', 'CSI'],
            ['Other Costs', '62710', 'Directors Fees & Expenses'],
            ['Other Costs', '62721', 'Donations'],
            ['Other Costs', '62999', 'Admin Expenses – Others'],
            ['Other Costs', '62727', 'Business Lunch'],

            // Depreciation of Fixed Assets
            ['Depreciation of Fixed Assets', '61201', 'Depreciation – GOIL Rebranding (Amortisation)'],
            ['Depreciation of Fixed Assets', '61202', 'Depreciation – Motor Vehicles'],
            ['Depreciation of Fixed Assets', '61203', 'Depreciation – Plant, Machinery & Equipment'],
            ['Depreciation of Fixed Assets', '61204', 'Depreciation – Right of Use'],
            ['Depreciation of Fixed Assets', '62301', 'Depreciation – Leasehold Land'],
            ['Depreciation of Fixed Assets', '62302', 'Depreciation – Buildings'],
            ['Depreciation of Fixed Assets', '62303', 'Depreciation – Computers & Accessories'],
            ['Depreciation of Fixed Assets', '62304', 'Depreciation – Furniture, Fittings & Equipment'],
            ['Depreciation of Fixed Assets', '62305', 'Depreciation – Software'],
            ['Depreciation of Fixed Assets', '62306', 'Depreciation – Freehold Land'],
            ['Depreciation of Fixed Assets', '62310', 'Depreciation (Assets Impairment)'],

            // Financial Charges
            ['Financial Charges', '63000', 'Finance Cost'],
            ['Financial Charges', '63101', 'Bank Charges'],
            ['Financial Charges', '63102', 'Bank Interest'],
            ['Financial Charges', '63103', 'Exchange Losses'],
            ['Financial Charges', '63104', 'Interest Payable to Third Parties'],
            ['Financial Charges', '63105', 'Floatation & Listing Expenses'],
            ['Financial Charges', '63999', 'Finance Cost – Others'],

            // Sundry Charges
            ['Sundry Charges', '61808', 'Bad Debts'],

            // Discount
            ['Discount', '61807', 'Discount Allowed'],

            // Health, Safety, Security & Environment
            ['Health, Safety, Security & Environment', '62726', 'ISO 9001:2015 Certification'],

            // Contingency
            ['Contingency', '99999', 'Contingency'],

            // Capital Expenditure
            ['Capital Expenditure', '11200', 'Building'],
            ['Capital Expenditure', '11300', 'Plant, Machinery & Equipments'],
            ['Capital Expenditure', '11400', 'Motor Vehicle'],
            ['Capital Expenditure', '11500', 'Furniture & Fittings'],
            ['Capital Expenditure', '11600', 'Software'],
            ['Capital Expenditure', '11700', 'Computers & Accessories'],
            ['Capital Expenditure', '11800', 'Right of Use Assets'],
            ['Capital Expenditure', '11900', 'Capital Work in Progress'],

            // Sales Revenue (41000 series)
            ['Sales Revenue', '41000-113', 'Diesel XP'],
            ['Sales Revenue', '41000-114', 'Super XP RON95'],
            ['Sales Revenue', '41000-120', 'Super XP RON91'],
            ['Sales Revenue', '41000-115', 'Mining Diesel'],
            ['Sales Revenue', '41000-116', 'Kerosene'],
            ['Sales Revenue', '41000-117', 'Fuel Oil (RFO)'],
            ['Sales Revenue', '41000-118', 'Premix'],
            ['Sales Revenue', '41000-401', 'Aviation Turbines Kerosene'],
            ['Sales Revenue', '41000-402', 'Marine Gas Oil-MGO'],
            ['Sales Revenue', '41000-404', 'LPG'],
            ['Sales Revenue', '41000-407', 'AGO Rig'],
            ['Sales Revenue', '41000-408', 'Ocean Bunkering'],
            ['Sales Revenue', '41000-500', 'CRM/LPG'],
            ['Sales Revenue', '41000-411-002-03', 'Diesel Gamma SAE 40 - 4 Litres'],
            ['Sales Revenue', '41000-411-012-01', 'GOIL Engine Oil 40/50 - 208 Litres'],
            ['Sales Revenue', '41000-411-017-03', 'Motor Oil HD 40/50 - 4 Litres'],
            ['Sales Revenue', '41000-414-007-01', 'Marinemix GM - 1 Litre'],
            ['Sales Revenue', '41000-411-017-01', 'Motor Oil HD 40/50 - 1 Litre'],
            ['Sales Revenue', '41000-411-002-01', 'Diesel Gamma SAE 40 - 1 Litre'],
            ['Sales Revenue', '41000-411-019-03', 'Super Motor Oil 20W/50 - 4 Litres'],
            ['Sales Revenue', '41000-411-026-01', 'Transformer Oil - 210 Litres'],
            ['Sales Revenue', '41000-411-002-02', 'Diesel Gamma SAE 40 - 208 Litres'],
            ['Sales Revenue', '41000-411-021-01', 'Super Turbo 15W/40 - 208 Litres'],
            ['Sales Revenue', '41000-411-002-05', 'Diesel Gamma SAE 40 - 5 Litres'],
            ['Sales Revenue', '41000-413-020-01', 'OSO 68 - 208 Litres'],
            ['Sales Revenue', '41000-411-020-08', 'Super Synthetic 5W/30 - 5 Litres'],
            ['Sales Revenue', '41000-411-020-12', 'Super Synthetic 5W/20 - 5 Litres'],
            ['Sales Revenue', '41000-411-027-01', 'GOIL Super Synthetic 0W20 - 5 Litres'],
            ['Sales Revenue', '41000-411-016-02', 'GOIL Super Taxi SAE 40 - 4 Litres'],
            ['Sales Revenue', '41000-411-020-11', 'Super Synthetic 5W/20 - 4 Litres'],
            ['Sales Revenue', '41000-411-020-17', 'Super Synthetic 5W/30 - 4 Litres'],
            ['Sales Revenue', '41000-416-003-01', 'Rotra HY 140 - 208 Litres'],
            ['Sales Revenue', '41000-411-002-04', 'Diesel Gamma SAE 40 - 20 Litres'],
            ['Sales Revenue', '41000-411-008-04', 'Diesel Synthetic SAE 15W/40 - 5 Litres'],
            ['Sales Revenue', '41000-416-001-01', 'ATF Dexron II - 1 Litre'],
            ['Sales Revenue', '41000-415-002-01', 'Radiator Coolant Solution - 1 Litre'],
            ['Sales Revenue', '41000-411-020-05', 'Super Synthetic 5W/40 - 5 Litres'],
            ['Sales Revenue', '41000-411-020-02', 'Super Synthetic 5W/40 - 4 Litres'],
            ['Sales Revenue', '41000-413-015-02', 'OSO 100 - 209 Litres'],
            ['Sales Revenue', '41000-411-023-01', 'Sigma Truck 15W/40 - 209 Litres'],
            ['Sales Revenue', '41000-413-020-02', 'OSO 68 - 209 Litres'],
            ['Sales Revenue', '41000-413-057-03', 'Transmission Fluid TO-4 SAE 30 - 209 Litres'],
            ['Sales Revenue', '41000-413-030-04', 'Transmission Fluid TO-4 SAE 50 - 209 Litres'],
            ['Sales Revenue', '41000-413-046-01', 'Deg. Compound - 200 Litres'],
            ['Sales Revenue', '41000-413-058-02', 'Rockdrill Oil 320 - 208 Litres'],
            ['Sales Revenue', '41000-413-004-02', 'Blasia 220 - 209 Litres'],
            ['Sales Revenue', '41000-413-024-02', 'Therm Oil 7 - 209 Litres'],
            ['Sales Revenue', '41000-412-014-01', 'Renolit Moly LX 2 - 180 kg'],

            // Cost of Sales (52000 series — same products, different ledger prefix)
            ['Cost of Sales', '52000-113', 'Diesel XP'],
            ['Cost of Sales', '52000-114', 'Super XP RON95'],
            ['Cost of Sales', '52000-120', 'Super XP RON91'],
            ['Cost of Sales', '52000-115', 'Mining Diesel'],
            ['Cost of Sales', '52000-116', 'Kerosene'],
            ['Cost of Sales', '52000-117', 'Fuel Oil (RFO)'],
            ['Cost of Sales', '52000-118', 'Premix'],
            ['Cost of Sales', '52000-401', 'Aviation Turbines Kerosene'],
            ['Cost of Sales', '52000-402', 'Marine Gas Oil-MGO'],
            ['Cost of Sales', '52000-404', 'LPG'],
            ['Cost of Sales', '52000-407', 'AGO Rig'],
            ['Cost of Sales', '52000-408', 'Ocean Bunkering'],
            ['Cost of Sales', '52000-500', 'CRM/LPG'],
            ['Cost of Sales', '52000-411-002-03', 'Diesel Gamma SAE 40 - 4 Litres'],
            ['Cost of Sales', '52000-411-012-01', 'GOIL Engine Oil 40/50 - 208 Litres'],
            ['Cost of Sales', '52000-411-017-03', 'Motor Oil HD 40/50 - 4 Litres'],
            ['Cost of Sales', '52000-414-007-01', 'Marinemix GM - 1 Litre'],
            ['Cost of Sales', '52000-411-017-01', 'Motor Oil HD 40/50 - 1 Litre'],
            ['Cost of Sales', '52000-411-002-01', 'Diesel Gamma SAE 40 - 1 Litre'],
            ['Cost of Sales', '52000-411-019-03', 'Super Motor Oil 20W/50 - 4 Litres'],
            ['Cost of Sales', '52000-411-026-01', 'Transformer Oil - 210 Litres'],
            ['Cost of Sales', '52000-411-002-02', 'Diesel Gamma SAE 40 - 208 Litres'],
            ['Cost of Sales', '52000-411-021-01', 'Super Turbo 15W/40 - 208 Litres'],
            ['Cost of Sales', '52000-411-002-05', 'Diesel Gamma SAE 40 - 5 Litres'],
            ['Cost of Sales', '52000-413-020-01', 'OSO 68 - 208 Litres'],
            ['Cost of Sales', '52000-411-020-08', 'Super Synthetic 5W/30 - 5 Litres'],
            ['Cost of Sales', '52000-411-020-12', 'Super Synthetic 5W/20 - 5 Litres'],
            ['Cost of Sales', '52000-411-027-01', 'GOIL Super Synthetic 0W20 - 5 Litres'],
            ['Cost of Sales', '52000-411-016-02', 'GOIL Super Taxi SAE 40 - 4 Litres'],
            ['Cost of Sales', '52000-411-020-11', 'Super Synthetic 5W/20 - 4 Litres'],
            ['Cost of Sales', '52000-411-020-17', 'Super Synthetic 5W/30 - 4 Litres'],
            ['Cost of Sales', '52000-416-003-01', 'Rotra HY 140 - 208 Litres'],
            ['Cost of Sales', '52000-411-002-04', 'Diesel Gamma SAE 40 - 20 Litres'],
            ['Cost of Sales', '52000-411-008-04', 'Diesel Synthetic SAE 15W/40 - 5 Litres'],
            ['Cost of Sales', '52000-416-001-01', 'ATF Dexron II - 1 Litre'],
            ['Cost of Sales', '52000-415-002-01', 'Radiator Coolant Solution - 1 Litre'],
            ['Cost of Sales', '52000-411-020-05', 'Super Synthetic 5W/40 - 5 Litres'],
            ['Cost of Sales', '52000-411-020-02', 'Super Synthetic 5W/40 - 4 Litres'],
            ['Cost of Sales', '52000-413-015-02', 'OSO 100 - 209 Litres'],
            ['Cost of Sales', '52000-411-023-01', 'Sigma Truck 15W/40 - 209 Litres'],
            ['Cost of Sales', '52000-413-020-02', 'OSO 68 - 209 Litres'],
            ['Cost of Sales', '52000-413-057-03', 'Transmission Fluid TO-4 SAE 30 - 209 Litres'],
            ['Cost of Sales', '52000-413-030-04', 'Transmission Fluid TO-4 SAE 50 - 209 Litres'],
            ['Cost of Sales', '52000-413-046-01', 'Deg. Compound - 200 Litres'],
            ['Cost of Sales', '52000-413-058-02', 'Rockdrill Oil 320 - 208 Litres'],
            ['Cost of Sales', '52000-413-004-02', 'Blasia 220 - 209 Litres'],
            ['Cost of Sales', '52000-413-024-02', 'Therm Oil 7 - 209 Litres'],
            ['Cost of Sales', '52000-412-014-01', 'Renolit Moly LX 2 - 180 kg'],
        ];

        foreach ($codes as [$catName, $code, $name]) {
            $catId = $catMap[$catName] ?? null;
            if (! $catId) {
                $this->command->warn("  Category not found: {$catName} — skipping {$code}");
                continue;
            }

            DB::table('account_codes')->updateOrInsert(
                ['code' => $code],
                [
                    'code'                => $code,
                    'name'                => $name,
                    'account_category_id' => $catId,
                    'is_active'           => true,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]
            );
        }

        $this->command->info('  ' . count($codes) . ' account codes done.');

        // ── Department ↔ Account Code assignments ─────────────────────────────
        $this->command->info('Seeding department account code assignments…');

        // [account_code, dept_code]
        $assignments = [
            ['61101', '02001'], ['61101', '11001'],
            ['61201', '05001'], ['61202', '05001'], ['61203', '05001'],
            ['61301', '01004'], ['61301', '05001'],
            ['61302', '01004'], ['61303', '01004'], ['61304', '01004'],
            ['61400', '02001'], ['61400', '11001'],
            ['61411', '02001'], ['61411', '11001'],
            ['61412', '02001'], ['61412', '11001'],
            ['61413', '02001'], ['61413', '11001'],
            ['61420', '02001'], ['61420', '11001'],
            ['61431', '11001'], ['61432', '11001'], ['61433', '11001'],
            ['61434', '02001'], ['61435', '02001'],
            ['61436', '11001'],
            ['61437', '02001'], ['61438', '02001'],
            ['61439', '02001'], ['61439', '11001'],
            ['61440', '01001'], ['61440', '01002'], ['61440', '01003'], ['61440', '01004'], ['61440', '01005'],
            ['61440', '01006'], ['61440', '01007'], ['61440', '01008'],
            ['61440', '02001'], ['61440', '03001'], ['61440', '04001'], ['61440', '05001'], ['61440', '06001'],
            ['61440', '07000'], ['61440', '08001'], ['61440', '09001'], ['61440', '10001'], ['61440', '11001'], ['61440', '12001'],
            ['61445', '11001'],
            ['61450', '02001'], ['61450', '11001'],
            ['61501', '07000'], ['61502', '07000'], ['61503', '07000'], ['61504', '07000'],
            ['61505', '01004'], ['61505', '07000'],
            ['61506', '07000'], ['61507', '07000'],
            ['61601', '08001'],
            ['61602', '10001'],
            ['61603', '06001'],
            ['61604', '02001'],
            ['61605', '01001'], ['61605', '01002'], ['61605', '01003'], ['61605', '01004'], ['61605', '01005'],
            ['61605', '01006'], ['61605', '01007'], ['61605', '01008'],
            ['61605', '02001'], ['61605', '03001'], ['61605', '04001'], ['61605', '05001'], ['61605', '06001'],
            ['61605', '07000'], ['61605', '08001'], ['61605', '09001'], ['61605', '10001'], ['61605', '11001'], ['61605', '12001'],
            ['61606', '02001'], ['61606', '10001'],
            ['61607', '06001'],
            ['61608', '10001'],
            ['61609', '07000'],
            ['61610', '02001'], ['61610', '10001'],
            ['61801', '01001'], ['61801', '01002'], ['61801', '01003'], ['61801', '01004'], ['61801', '01005'],
            ['61801', '01006'], ['61801', '01007'], ['61801', '01008'],
            ['61801', '02001'], ['61801', '03001'], ['61801', '04001'], ['61801', '05001'], ['61801', '06001'],
            ['61801', '07000'], ['61801', '08001'], ['61801', '09001'], ['61801', '10001'], ['61801', '11001'], ['61801', '12001'],
            ['61802', '01003'],
            ['61803', '01001'], ['61803', '01002'], ['61803', '01003'], ['61803', '01004'], ['61803', '01005'],
            ['61803', '01006'], ['61803', '01007'], ['61803', '01008'],
            ['61803', '02001'], ['61803', '03001'], ['61803', '04001'], ['61803', '05001'], ['61803', '06001'],
            ['61803', '07000'], ['61803', '08001'], ['61803', '09001'], ['61803', '10001'], ['61803', '11001'], ['61803', '12001'],
            ['61804', '10001'],
            ['61805', '01008'], ['61805', '04001'],
            ['61806', '02001'],
            ['61807', '04001'], ['61807', '05001'], ['61807', '11001'],
            ['61808', '05001'],
            ['61809', '02001'],
            ['61810', '04001'],
            ['61812', '01007'], ['61812', '03001'],
            ['61813', '08001'],
            ['61814', '01001'], ['61814', '01002'], ['61814', '01003'], ['61814', '01004'], ['61814', '01005'],
            ['61814', '01006'], ['61814', '01007'], ['61814', '01008'],
            ['61814', '02001'], ['61814', '03001'], ['61814', '04001'], ['61814', '05001'], ['61814', '06001'],
            ['61814', '07000'], ['61814', '08001'], ['61814', '09001'], ['61814', '10001'], ['61814', '11001'], ['61814', '12001'],
            ['61815', '03001'], ['61815', '04001'],
            ['62101', '07000'], ['62102', '07000'], ['62103', '07000'], ['62104', '07000'],
            ['62105', '01004'],
            ['62106', '07000'], ['62107', '07000'], ['62108', '07000'],
            ['62201', '06001'], ['62202', '06001'],
            ['62203', '01001'], ['62203', '01002'], ['62203', '01003'], ['62203', '01004'], ['62203', '01005'],
            ['62203', '01006'], ['62203', '01007'], ['62203', '01008'],
            ['62203', '02001'], ['62203', '03001'], ['62203', '04001'], ['62203', '05001'], ['62203', '06001'],
            ['62203', '07000'], ['62203', '08001'], ['62203', '09001'], ['62203', '10001'], ['62203', '11001'], ['62203', '12001'],
            ['62301', '05001'], ['62302', '05001'], ['62303', '05001'], ['62304', '05001'],
            ['62305', '05001'], ['62306', '05001'], ['62310', '05001'],
            ['62401', '01004'], ['62402', '01004'], ['62403', '01004'], ['62404', '01004'],
            ['62405', '01004'], ['62406', '01004'], ['62407', '01004'],
            ['62501', '01003'], ['62502', '01003'],
            ['62601', '07000'], ['62602', '07000'], ['62603', '07000'], ['62604', '07000'], ['62605', '07000'],
            ['62606', '01001'], ['62606', '01002'], ['62606', '01003'], ['62606', '05001'], ['62606', '07000'], ['62606', '09001'],
            ['62607', '07000'], ['62608', '07000'], ['62609', '07000'], ['62610', '07000'],
            ['62611', '07000'],
            ['62612', '01001'], ['62612', '01002'], ['62612', '01003'], ['62612', '01004'], ['62612', '01005'],
            ['62612', '01006'], ['62612', '01007'], ['62612', '01008'],
            ['62612', '02001'], ['62612', '03001'], ['62612', '04001'], ['62612', '05001'], ['62612', '06001'],
            ['62612', '07000'], ['62612', '08001'], ['62612', '09001'], ['62612', '10001'], ['62612', '11001'], ['62612', '12001'],
            ['62613', '01001'], ['62613', '01002'], ['62613', '01003'], ['62613', '01004'], ['62613', '01005'],
            ['62613', '01006'], ['62613', '01007'], ['62613', '01008'],
            ['62613', '02001'], ['62613', '03001'], ['62613', '04001'], ['62613', '05001'], ['62613', '06001'],
            ['62613', '07000'], ['62613', '08001'], ['62613', '09001'], ['62613', '10001'], ['62613', '11001'], ['62613', '12001'],
            ['62614', '07000'],
            ['62615', '01003'], ['62615', '03001'], ['62615', '07000'], ['62615', '11001'], ['62615', '12001'],
            ['62616', '01001'], ['62616', '01002'], ['62616', '01003'], ['62616', '01004'], ['62616', '01005'],
            ['62616', '01006'], ['62616', '01007'], ['62616', '01008'],
            ['62616', '02001'], ['62616', '03001'], ['62616', '04001'], ['62616', '05001'], ['62616', '06001'],
            ['62616', '07000'], ['62616', '08001'], ['62616', '09001'], ['62616', '10001'], ['62616', '11001'], ['62616', '12001'],
            ['62617', '07000'],
            ['62620', '01003'], ['62620', '07000'],
            ['62701', '01001'], ['62701', '05001'], ['62701', '08001'], ['62701', '09001'],
            ['62702', '05001'],
            ['62703', '07000'],
            ['62704', '01006'],
            ['62710', '01001'],
            ['62711', '01001'], ['62712', '01001'],
            ['62721', '01006'],
            ['62722', '02001'],
            ['62723', '07000'],
            ['62724', '01001'], ['62724', '01002'], ['62724', '01003'], ['62724', '01004'], ['62724', '01005'],
            ['62724', '01006'], ['62724', '01007'], ['62724', '01008'],
            ['62724', '02001'], ['62724', '03001'], ['62724', '04001'], ['62724', '05001'], ['62724', '06001'],
            ['62724', '07000'], ['62724', '08001'], ['62724', '09001'], ['62724', '10001'], ['62724', '11001'], ['62724', '12001'],
            ['62725', '01001'], ['62725', '01002'], ['62725', '01003'], ['62725', '01004'], ['62725', '01005'],
            ['62725', '01006'], ['62725', '01007'], ['62725', '01008'],
            ['62725', '02001'], ['62725', '03001'], ['62725', '04001'], ['62725', '05001'], ['62725', '06001'],
            ['62725', '07000'], ['62725', '08001'], ['62725', '09001'], ['62725', '10001'], ['62725', '11001'], ['62725', '12001'],
            ['62726', '01003'],
            ['62727', '01001'], ['62727', '01002'], ['62727', '01003'], ['62727', '01004'], ['62727', '01005'],
            ['62727', '01006'], ['62727', '01007'], ['62727', '01008'],
            ['62727', '02001'], ['62727', '03001'], ['62727', '04001'], ['62727', '05001'], ['62727', '06001'],
            ['62727', '07000'], ['62727', '08001'], ['62727', '09001'], ['62727', '10001'], ['62727', '11001'], ['62727', '12001'],
            ['62728', '01001'], ['62728', '01006'], ['62728', '09001'],
            ['62740', '02001'], ['62741', '02001'],
            ['62751', '02001'], ['62752', '02001'], ['62753', '02001'], ['62754', '02001'],
            ['62760', '02001'],
            ['62761', '06001'],
            ['62771', '03001'], ['62771', '04001'], ['62771', '10001'],
            ['62772', '01001'], ['62772', '01002'], ['62772', '01003'], ['62772', '01004'], ['62772', '01005'],
            ['62772', '01006'], ['62772', '01007'], ['62772', '01008'],
            ['62772', '02001'], ['62772', '03001'], ['62772', '04001'], ['62772', '05001'], ['62772', '06001'],
            ['62772', '07000'], ['62772', '08001'], ['62772', '09001'], ['62772', '10001'], ['62772', '11001'], ['62772', '12001'],
            ['62773', '01001'], ['62773', '01002'], ['62773', '01003'], ['62773', '01004'], ['62773', '01005'],
            ['62773', '01006'], ['62773', '01007'], ['62773', '01008'],
            ['62773', '02001'], ['62773', '03001'], ['62773', '04001'], ['62773', '05001'], ['62773', '06001'],
            ['62773', '07000'], ['62773', '08001'], ['62773', '09001'], ['62773', '10001'], ['62773', '11001'], ['62773', '12001'],
            ['62774', '01001'], ['62774', '01002'], ['62774', '01003'], ['62774', '01004'], ['62774', '01005'],
            ['62774', '01006'], ['62774', '01007'], ['62774', '01008'],
            ['62774', '02001'], ['62774', '03001'], ['62774', '04001'], ['62774', '05001'], ['62774', '06001'],
            ['62774', '07000'], ['62774', '08001'], ['62774', '09001'], ['62774', '10001'], ['62774', '11001'], ['62774', '12001'],
            ['62775', '10001'],
            ['62776', '07000'],
            ['62777', '06001'],
            ['62778', '01001'], ['62778', '01002'], ['62778', '01003'], ['62778', '01004'], ['62778', '01005'],
            ['62778', '01006'], ['62778', '01007'], ['62778', '01008'],
            ['62778', '02001'], ['62778', '03001'], ['62778', '04001'], ['62778', '05001'], ['62778', '06001'],
            ['62778', '07000'], ['62778', '08001'], ['62778', '09001'], ['62778', '10001'], ['62778', '11001'], ['62778', '12001'],
            ['62779', '01001'], ['62779', '01002'], ['62779', '01003'], ['62779', '01004'], ['62779', '01005'],
            ['62779', '01006'], ['62779', '01007'], ['62779', '01008'],
            ['62779', '02001'], ['62779', '03001'], ['62779', '04001'], ['62779', '05001'], ['62779', '06001'],
            ['62779', '07000'], ['62779', '08001'], ['62779', '09001'], ['62779', '10001'], ['62779', '11001'], ['62779', '12001'],
            ['62780', '02001'],
            ['62781', '05001'], ['62782', '05001'], ['62783', '05001'],
            ['62784', '04001'], ['62784', '05001'],
            ['62785', '09001'],
            ['62999', '07000'],
            ['63000', '05001'], ['63101', '05001'], ['63102', '05001'], ['63103', '05001'],
            ['63104', '05001'], ['63105', '05001'], ['63999', '05001'],
        ];

        $deptMap = DB::table('departments')->pluck('id', 'code')->all();
        $codeMap = DB::table('account_codes')->pluck('id', 'code')->all();

        $inserted = 0;
        foreach ($assignments as [$accCode, $deptCode]) {
            $deptId = $deptMap[$deptCode] ?? null;
            $codeId = $codeMap[$accCode] ?? null;
            if (! $deptId || ! $codeId) {
                $this->command->warn("  Skipping {$accCode} → {$deptCode}: not found in DB");
                continue;
            }
            DB::table('department_account_codes')->updateOrInsert(
                ['department_id' => $deptId, 'account_code_id' => $codeId],
                ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
            $inserted++;
        }

        $this->command->info('  ' . $inserted . ' department–code assignments done.');

        // ── Revenue / Cost of Sales assignments ───────────────────────────────────
        $this->command->info('Seeding revenue & cost of sales assignments…');

        // Fuels Marketing (04001): Diesel XP, Super XP RON95, Super XP RON91 — revenue + COS
        $fuelsMktCodes = [
            '41000-113', '41000-114', '41000-120',
            '52000-113', '52000-114', '52000-120',
        ];

        // TSPM (03001): all remaining revenue & COS codes
        $allRevCosCodes = DB::table('account_codes')
            ->whereIn('account_category_id', DB::table('account_categories')
                ->whereIn('code', ['CAT-REV', 'CAT-COS'])
                ->pluck('id'))
            ->pluck('code')
            ->all();

        $tspmCodes = array_values(array_diff($allRevCosCodes, $fuelsMktCodes));

        $revCosAssignments = [];
        foreach ($fuelsMktCodes as $code) {
            $revCosAssignments[] = [$code, '04001'];
        }
        foreach ($tspmCodes as $code) {
            $revCosAssignments[] = [$code, '03001'];
        }

        $revCosInserted = 0;
        foreach ($revCosAssignments as [$accCode, $deptCode]) {
            $deptId = $deptMap[$deptCode] ?? null;
            $codeId = $codeMap[$accCode] ?? null;
            if (! $deptId || ! $codeId) {
                $this->command->warn("  Skipping {$accCode} → {$deptCode}: not found in DB");
                continue;
            }
            DB::table('department_account_codes')->updateOrInsert(
                ['department_id' => $deptId, 'account_code_id' => $codeId],
                ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
            $revCosInserted++;
        }

        $this->command->info('  ' . $revCosInserted . ' revenue & cost of sales assignments done.');

        // ── Approval Stages ───────────────────────────────────────────────────────
        $this->command->info('Seeding approval stages…');

        $stages = [
            ['name' => 'Department Head',    'order' => 1, 'role_name' => 'department_head'],
            ['name' => 'Finance Review',     'order' => 2, 'role_name' => 'finance_reviewer'],
            ['name' => 'GCEO & MD Approval', 'order' => 3, 'role_name' => 'gceo'],
            ['name' => 'Board Approval',     'order' => 4, 'role_name' => 'board'],
        ];

        foreach ($stages as $stage) {
            DB::table('approval_stages')->updateOrInsert(
                ['order' => $stage['order']],
                array_merge($stage, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        $this->command->info('  ' . count($stages) . ' approval stages done.');

        $this->command->newLine();
        $this->command->info('✓  GOIL data loaded successfully.');
        $this->command->info('   Next: open a Budget Period.');
    }
}
