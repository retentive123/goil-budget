<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Loads GOIL's departments, account categories, and account codes.
 * Re-runnable — uses updateOrInsert / insertOrIgnore throughout.
 *
 * Run: php artisan db:seed --class=GoilDataSeeder
 */
class GoilDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Departments ────────────────────────────────────────────────────
        $this->command->info('Seeding departments…');

        // approval_route: 'gceo' (default) | 'coo' | 'direct'
        // COO-routed: Fuels Marketing, TSPM, Technical Services, Research, Estates, Non Fuels, Operations
        $departments = [
            ['name' => 'GCEO/MD',              'code' => '01001', 'approval_route' => 'gceo',   'description' => 'Office of the Group Chief Executive Officer & Managing Director. Provides overall strategic leadership and corporate governance.'],
            ['name' => 'COO',                  'code' => '15000', 'approval_route' => 'gceo',   'description' => 'Office of the Chief Operating Officer. Oversees operational efficiency and coordination across all business units.'],
            ['name' => 'Finance',              'code' => '05001', 'approval_route' => 'gceo',   'description' => 'Financial planning, accounting, treasury, and management reporting for the company.'],
            ['name' => 'Admin/HR',             'code' => '07000', 'approval_route' => 'gceo',   'description' => 'Administration & Human Resources. Manages human capital, staff welfare, payroll, and general administrative services.'],
            ['name' => 'Operations',           'code' => '02001', 'approval_route' => 'coo',    'description' => 'Manages day-to-day depot, station, and distribution operations including fuel storage and logistics.'],
            ['name' => 'CIA',                  'code' => '01002', 'approval_route' => 'gceo',   'description' => 'Corporate Internal Audit. Provides independent assurance on risk management, internal controls, and governance processes.'],
            ['name' => 'HSSE',                 'code' => '01003', 'approval_route' => 'gceo',   'description' => 'Health, Safety, Security & Environment. Ensures compliance with HSSE standards and policies across all operations.'],
            ['name' => 'TSPM',                 'code' => '03001', 'approval_route' => 'coo',    'description' => 'Technical Services & Project Management. Oversees engineering projects, technical infrastructure, and asset maintenance.'],
            ['name' => 'Estates',              'code' => '18000', 'approval_route' => 'coo',    'description' => 'Manages company-owned properties, buildings, land leases, and residential accommodation.'],
            ['name' => 'Legal',                'code' => '01004', 'approval_route' => 'gceo',   'description' => 'Legal Affairs. Provides legal advisory services, contract management, and regulatory compliance oversight.'],
            ['name' => 'Research & Bus. Int.', 'code' => '01005', 'approval_route' => 'coo',    'description' => 'Research & Business Intelligence. Conducts market research, competitive analysis, and product development studies.'],
            ['name' => 'Technical Services',   'code' => '16000', 'approval_route' => 'coo',    'description' => 'Technical Services. Manages the company\'s vehicle fleet, transport logistics, and related procurement.'],
            ['name' => 'Non Fuels',            'code' => '17000', 'approval_route' => 'coo',    'description' => 'Manages non-fuel product lines including lubricants, LPG, GoCard, and other retail offerings.'],
            ['name' => 'Procurement',          'code' => '01007', 'approval_route' => 'gceo',   'description' => 'Manages sourcing, purchasing, and supply chain activities for all goods and services across the company.'],
            ['name' => 'Fuels Marketing',      'code' => '04001', 'approval_route' => 'coo',    'description' => 'Manages marketing and sales of petroleum products including petrol, diesel, kerosene, LPG, and aviation fuel.'],
            ['name' => 'IT',                   'code' => '06001', 'approval_route' => 'gceo',   'description' => 'Information Technology. Manages ICT infrastructure, enterprise systems, and digital transformation initiatives.'],
            ['name' => 'Business Development', 'code' => '01006', 'approval_route' => 'gceo',   'description' => 'Identifies and develops new business opportunities, strategic partnerships, and market expansion initiatives.'],
            ['name' => 'Corporate Affairs',    'code' => '08001', 'approval_route' => 'gceo',   'description' => 'Public relations, stakeholder engagement, corporate communications, and CSR initiatives.'],
            ['name' => 'Risk',                 'code' => '01008', 'approval_route' => 'gceo',   'description' => 'Enterprise Risk Management. Identifies, assesses, and mitigates operational, financial, and strategic risks.'],
        ];

        foreach ($departments as $dept) {
            DB::table('departments')->updateOrInsert(
                ['code' => $dept['code']],
                array_merge($dept, [
                    'budget_type' => 'expense',
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ])
            );
        }

        $this->command->info('  ' . count($departments) . ' departments done.');

        // ── 2. Account Categories ─────────────────────────────────────────────
        $this->command->info('Seeding account categories…');

        $categories = [
            ['CAT-01',      'Wages & Salaries',                        'expense'],
            ['CAT-02',      'Travelling & Transport',                  'expense'],
            ['CAT-03',      'Other Personnel Cost',                    'expense'],
            ['CAT-04',      'Various Materials',                       'expense'],
            ['CAT-05',      'Electricity & Water',                     'expense'],
            ['CAT-06',      'Maintenance & Running Cost',              'expense'],
            ['CAT-07',      'Various Services',                        'expense'],
            ['CAT-09',      'Professional Service',                    'expense'],
            ['CAT-10',      'Taxes / Levies / Permits',                'expense'],
            ['CAT-11',      'Equipment Hiring & Rental',               'expense'],
            ['CAT-12',      'Stations Land Leasing',                   'expense'],
            ['CAT-13',      'Insurance Premium',                       'expense'],
            ['CAT-14',      'Post & Telecommunication',                'expense'],
            ['CAT-15',      'Sales Promotion & Advertising',           'expense'],
            ['CAT-16',      'Other Costs',                             'expense'],
            ['CAT-17',      'Depreciation of Fixed Assets',            'expense'],
            ['CAT-18',      'Financial Charges',                       'expense'],
            ['CAT-19',      'Sundry Charges',                          'expense'],
            ['CAT-20',      'Discount',                                'expense'],
            ['CAT-21',      'Health, Safety, Security & Environment',  'expense'],
            ['CAT-99',      'Contingency',                             'expense'],
            ['CAT-CAPEX',   'Capital Expenditure',                     'capital_expenditure'],
            ['CAT-REV',     'Sales Revenue',                           'revenue'],
            ['CAT-OTH-INC', 'Other Income',                            'revenue'],
            ['CAT-COS',     'Cost of Sales',                           'expense'],
        ];

        foreach ($categories as [$code, $name, $budgetType]) {
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
        }

        $this->command->info('  ' . count($categories) . ' categories done.');

        // ── 3. Account Codes ──────────────────────────────────────────────────
        $this->command->info('Seeding account codes…');

        $catMap = DB::table('account_categories')->pluck('id', 'code')->all();

        // Format: [category_code, account_code, name, scope]
        $codes = [

            // ── 1. Salaries and Allowances ────────────────────────────────────
            ['CAT-01', '62608', 'Normal Salaries & Wages',                                          'department'],
            ['CAT-01', '62609', 'Overtime',                                                         'department'],
            ['CAT-01', '62610', "SSF Employer's Contribution",                                      'department'],
            ['CAT-01', '62603', 'Employer Tier 3 Contri',                                           'department'],
            ['CAT-01', '62602', 'Early Retirement',                                                 'department'],
            ['CAT-01', '62601', 'Annual Bonus',                                                     'department'],
            ['CAT-01', '62604', 'Staff Cost - Month 13',                                            'department'],
            ['CAT-01', '62612', 'Subscriptions to Professional Bodies',                             'department'],
            ['CAT-01', '15299', 'Staff Loans Recovery Clearing Account',                            'department'],

            // ── 2. Travelling & Transport ─────────────────────────────────────
            ['CAT-02', '62614', 'Transfers & Relocation Allowances',                                'department'],
            ['CAT-02', '62778', 'Transport Expenses',                                               'department'],
            ['CAT-02', '62779', 'Training (Overseas)',                                              'department'],

            // ── 3. Other Personnel Cost ───────────────────────────────────────
            ['CAT-03', '61805', 'Training - Frontline',                                             'department'],
            ['CAT-03', '62618', 'Seasonal End of Year Activities',                                  'department'],
            ['CAT-03', '62607', 'Medical',                                                          'department'],
            ['CAT-03', '62611', 'Staff Welfare',                                                    'department'],
            ['CAT-03', '62613', 'Training - Local',                                                 'department'],
            ['CAT-03', '62615', 'Uniforms',                                                         'department'],
            ['CAT-03', '62616', 'Staff Cost - Misc.',                                               'department'],
            ['CAT-03', '62617', 'Staff Cost - Retired Staff Medicals',                              'department'],
            ['CAT-03', '62619', 'Scholarship',                                                      'department'],
            ['CAT-03', '62620', 'Staff Cost - Medical Screening',                                   'department'],
            ['CAT-03', '62723', 'Canteen',                                                          'department'],
            ['CAT-03', '62724', 'Fitness & Recreational Assistance',                                'department'],
            ['CAT-03', '62725', 'Hotel & Outstation Expenses',                                      'department'],

            // ── 4. Various Materials ──────────────────────────────────────────
            ['CAT-04', '61603', 'Coupon Production',                                                'department'],
            ['CAT-04', '61607', 'POS/GoCard Expenses',                                              'department'],
            ['CAT-04', '62202', 'Computing Expenses - Forms & Accessories',                         'department'],
            ['CAT-04', '62773', 'Printing & Stationery',                                            'department'],

            // ── 5. Electricity & Water ────────────────────────────────────────
            ['CAT-05', '61604', 'Electricity for Depots',                                           'department'],
            ['CAT-05', '61806', 'Water for Depots',                                                 'department'],
            ['CAT-05', '62722', 'Electricity - Offices & Houses',                                   'department'],
            ['CAT-05', '62780', 'Water - Offices & Houses',                                         'department'],

            // ── 6. Maintenance & Running Cost ─────────────────────────────────
            ['CAT-06', '61400', 'Maintenance of Stations',                                          'department'],
            ['CAT-06', '61411', 'Genset Maint. & Repairs',                                          'department'],
            ['CAT-06', '61412', 'Pump Maint. & Repairs',                                            'department'],
            ['CAT-06', '61413', 'General Maint. & Repairs',                                         'department'],
            ['CAT-06', '61420', 'Maintenance of Consumer Installations',                            'department'],
            ['CAT-06', '61431', 'Maint. of Aviation Depot - Accra',                                 'department'],
            ['CAT-06', '61432', 'Maint. of Aviation Depot - Kumasi',                                'department'],
            ['CAT-06', '61433', 'Maint. of Aviation Depot - Takoradi',                              'department'],
            ['CAT-06', '61445', 'Maint. of Aviation Depot - Tamale',                                'department'],
            ['CAT-06', '61434', 'Maint. of LPG & Lubes Depot - Kumasi',                             'department'],
            ['CAT-06', '61435', 'Maint. of LPG & Lubes Depot - Tema',                               'department'],
            ['CAT-06', '61436', "Maint. of Bunkering - Sekondi Naval Base & T'adi Harbour",         'department'],
            ['CAT-06', '61437', 'Maint. of Lubes Depot - Takoradi',                                 'department'],
            ['CAT-06', '61438', 'Maint. of Lubes Depot - Tamale',                                   'department'],
            ['CAT-06', '61439', 'Maint. of Installations',                                          'department'],
            ['CAT-06', '61440', 'Furniture and Office Equipment',                                   'department'],
            ['CAT-06', '61450', 'Maintenance of Plant Machinery & Equipment',                       'department'],
            ['CAT-06', '61501', 'Revenue Generating Vehicles - Cost of Labour Charges',             'department'],
            ['CAT-06', '61502', 'Revenue Generating Vehicles - Cost of Spares',                     'department'],
            ['CAT-06', '61503', 'Revenue Generating Vehicles - Cost of Tyres',                      'department'],
            ['CAT-06', '61504', 'Revenue Generating Vehicles - Insurance',                          'department'],
            ['CAT-06', '61505', 'Revenue Generating Vehicles - Fuels',                              'department'],
            ['CAT-06', '61506', 'Revenue Generating Vehicles - Lubes',                              'department'],
            ['CAT-06', '61507', 'Revenue Generating Vehicles - Permits & Licensing',                'department'],
            ['CAT-06', '61610', 'Rent - Administration',                                            'department'],
            ['CAT-06', '61809', 'Aro Warehouse Maintenance',                                        'department'],
            ['CAT-06', '61810', 'Take Over Stations Commissions',                                   'department'],
            ['CAT-06', '61811', 'Lubes Production / Business Development',                          'department'],
            ['CAT-06', '61812', 'Clearing & Handling Charges',                                      'department'],
            ['CAT-06', '62101', 'Administration Vehicles - Cost of Labour Charges & Spares',        'department'],
            ['CAT-06', '62102', 'Administration Vehicles - Cost of Spares',                         'department'],
            ['CAT-06', '62103', 'Administration Vehicles - Cost of Tyres',                          'department'],
            ['CAT-06', '62104', 'Administration Vehicles - Fuels Internal Consumption',             'department'],
            ['CAT-06', '62106', 'Administration Vehicles - Lubes',                                  'department'],
            ['CAT-06', '62201', 'Computing Expenses - Computer Software & Network Maintenance',     'department'],
            ['CAT-06', '62501', 'HSSEQ Expenses',                                                   'department'],
            ['CAT-06', '62740', 'Maintenance of Office',                                            'department'],
            ['CAT-06', '62741', 'Maintenance of Gensets - Offices',                                 'department'],
            ['CAT-06', '62751', 'Maint. of Guest House',                                            'department'],
            ['CAT-06', '62752', 'Maint. of Residential Accommodation',                              'department'],
            ['CAT-06', '62753', 'Maint. of Head Office',                                            'department'],
            ['CAT-06', '62754', 'Maint. - Other Residences',                                        'department'],
            ['CAT-06', '62760', 'Maintenance of Office Equipment',                                  'department'],
            ['CAT-06', '62761', 'Coupons Replacement',                                              'department'],
            ['CAT-06', '62777', 'Software License',                                                 'department'],
            ['CAT-06', '62781', 'Stock Losses',                                                     'department'],
            ['CAT-06', '62782', 'Damaged Stocks',                                                   'department'],
            ['CAT-06', '62783', 'Debt Recovery',                                                    'department'],
            ['CAT-06', '62784', 'Loyalty Award',                                                    'department'],
            ['CAT-06', '62785', 'Housing Rent for Personnel',                                       'department'],

            // ── 7. Various Services ───────────────────────────────────────────
            ['CAT-07', '62502', 'Security Services',                                                'department'],
            ['CAT-07', '62703', 'Contract Labour',                                                  'department'],
            ['CAT-07', '62776', 'Cleaning Services',                                                'department'],

            // ── 9. Professional Service ───────────────────────────────────────
            ['CAT-09', '61101', 'Calibration of U/G Tanks',                                         'department'],
            ['CAT-09', '61802', 'Standard Boards & Statutory',                                      'department'],
            ['CAT-09', '61804', 'Survey Fees',                                                      'department'],
            ['CAT-09', '62701', 'Annual General Meeting',                                           'department'],
            ['CAT-09', '62702', 'Audit Fees',                                                       'department'],
            ['CAT-09', '62711', 'Directors Remuneration',                                           'department'],
            ['CAT-09', '62712', 'Directors Expenses',                                               'department'],
            ['CAT-09', '62774', 'Professional Services Fees & Charges',                             'department'],

            // ── 10. Taxes / Levies / Permits ──────────────────────────────────
            ['CAT-10', '61608', 'Ground Rent & Parking Lot',                                        'department'],
            ['CAT-10', '61609', 'Motorway Toll - Revenue Generating Vehicles',                      'department'],
            ['CAT-10', '62107', 'Administration Vehicles - Permits & Licenses',                     'department'],
            ['CAT-10', '62108', 'Administration Vehicles - Motorway Tolls',                         'department'],
            ['CAT-10', '62771', 'Permit & Licenses',                                                'department'],
            ['CAT-10', '62775', 'Property Rate',                                                    'department'],

            // ── 11. Equipment Hiring & Rental ─────────────────────────────────
            ['CAT-11', '61605', 'Equipment Hiring',                                                 'department'],
            ['CAT-11', '61606', 'General Rates on Buildings - Depots & Stations',                   'department'],

            // ── 12. Stations Land Leasing ─────────────────────────────────────
            ['CAT-12', '61602', 'Annual Lease',                                                     'department'],

            // ── 13. Insurance Premium ─────────────────────────────────────────
            ['CAT-13', '61301', 'Insurance - Depots / Depreciation - Plant',                        'department'],
            ['CAT-13', '61302', 'Insurance - Motor Vehicles',                                       'department'],
            ['CAT-13', '61303', 'Insurance - Plant Machinery & Equipment',                          'department'],
            ['CAT-13', '61304', 'Insurance - Stations Company Owned',                               'department'],
            ['CAT-13', '62105', 'Administration Vehicles - Insurance',                              'department'],
            ['CAT-13', '62401', 'Insurance - Head Office',                                          'department'],
            ['CAT-13', '62402', 'Insurance - Residences',                                           'department'],
            ['CAT-13', '62403', 'Insurance - Goods in Transit',                                     'department'],
            ['CAT-13', '62404', 'Insurance - Fidelity Guarantee',                                   'department'],
            ['CAT-13', '62405', 'Insurance - Public Liability',                                     'department'],
            ['CAT-13', '62406', 'Insurance - Burglary',                                             'department'],
            ['CAT-13', '62407', 'Assets All Risk',                                                  'department'],
            ['CAT-13', '62605', 'Staff Cost - Life Insurance',                                      'department'],

            // ── 14. Post & Telecommunication ──────────────────────────────────
            ['CAT-14', '62203', 'Computing Expenses - WAN & Internet',                              'department'],
            ['CAT-14', '62772', 'Postage & Telephone',                                              'department'],

            // ── 15. Sales Promotion & Advertising ────────────────────────────
            ['CAT-15', '61601', 'Advertising',                                                      'department'],
            ['CAT-15', '61801', 'Sales Promotion',                                                  'department'],
            ['CAT-15', '61813', 'Business Promotion - Brands Ambassador',                           'department'],
            ['CAT-15', '61814', 'Branded Items Events / Business Promotion',                        'department'],
            ['CAT-15', '61815', 'Bonus - Frontline',                                                'department'],
            ['CAT-15', '62728', 'Sponsorship',                                                      'department'],

            // ── 16. Other Costs ───────────────────────────────────────────────
            ['CAT-16', '61803', 'Subscriptions - Various Business Associations',                    'department'],
            ['CAT-16', '62704', 'CSI',                                                              'department'],
            ['CAT-16', '62710', 'Directors Fees & Expenses',                                        'department'],
            ['CAT-16', '62721', 'Donations',                                                        'department'],
            ['CAT-16', '62727', 'Business Lunch',                                                   'department'],
            ['CAT-16', '62999', 'Admin Expenses - Others',                                          'department'],

            // ── 17. Depreciation of Fixed Assets ─────────────────────────────
            ['CAT-17', '61201', 'Depreciation - GOIL Rebranding Amortisation',                      'department'],
            ['CAT-17', '61202', 'Depreciation - Motor Vehicles',                                    'department'],
            ['CAT-17', '61203', 'Depreciation - Plant Machinery & Equipment',                       'department'],
            ['CAT-17', '62301', 'Depreciation - Leasehold Land',                                    'department'],
            ['CAT-17', '62302', 'Depreciation - Buildings',                                         'department'],
            ['CAT-17', '62303', 'Depreciation - Computers & Accessories',                           'department'],
            ['CAT-17', '62304', 'Depreciation - Furniture Fittings & Equipment',                    'department'],
            ['CAT-17', '62305', 'Depreciation - Software',                                          'department'],
            ['CAT-17', '62306', 'Depreciation - Freehold Land',                                     'department'],
            ['CAT-17', '62310', 'Depreciation - Assets Impairment',                                 'department'],

            // ── 18. Financial Charges ─────────────────────────────────────────
            ['CAT-18', '63000', 'Finance Cost',                                                     'department'],
            ['CAT-18', '63101', 'Bank Charges',                                                     'department'],
            ['CAT-18', '63102', 'Bank Interest',                                                    'department'],
            ['CAT-18', '63103', 'Exchange Losses',                                                  'department'],
            ['CAT-18', '63104', 'Interest Payable to Third Parties',                                'department'],
            ['CAT-18', '63105', 'Floatation & Listing Expenses',                                    'department'],
            ['CAT-18', '63999', 'Finance Cost - Others',                                            'department'],

            // ── 19. Sundry Charges ────────────────────────────────────────────
            ['CAT-19', '61808', 'Bad Debts',                                                        'department'],

            // ── 20. Discount ──────────────────────────────────────────────────
            ['CAT-20', '61807', 'Discount Allowed',                                                 'department'],

            // ── 21. Health, Safety, Security & Environment ────────────────────
            ['CAT-21', '62726', 'ISO Integrated Management Systems',                                'department'],

            // ── Contingency ───────────────────────────────────────────────────
            ['CAT-99', '99999', 'Contingency',                                                      'department'],

            // ── Capital Expenditure ───────────────────────────────────────────
            ['CAT-CAPEX', '11200', 'Building',                                                      'department'],
            ['CAT-CAPEX', '11300', 'Plant, Machinery & Equipment',                                  'department'],
            ['CAT-CAPEX', '11400', 'Motor Vehicle',                                                 'department'],
            ['CAT-CAPEX', '11500', 'Furniture & Fittings',                                          'department'],
            ['CAT-CAPEX', '11600', 'Software',                                                      'department'],
            ['CAT-CAPEX', '11700', 'Computers & Accessories',                                       'department'],
            ['CAT-CAPEX', '11800', 'Right of Use Assets',                                           'department'],
            ['CAT-CAPEX', '11900', 'Capital Work in Progress',                                      'department'],

            // ── Sales Revenue (41000 series) ──────────────────────────────────
            ['CAT-REV', '41000-113',          'Diesel XP',                                          'department'],
            ['CAT-REV', '41000-114',          'Super XP RON95',                                     'department'],
            ['CAT-REV', '41000-120',          'Super XP RON91',                                     'department'],
            ['CAT-REV', '41000-115',          'Mining Diesel',                                      'department'],
            ['CAT-REV', '41000-116',          'Kerosene',                                           'department'],
            ['CAT-REV', '41000-117',          'Fuel Oil (RFO)',                                     'department'],
            ['CAT-REV', '41000-118',          'Premix',                                             'department'],
            ['CAT-REV', '41000-401',          'Aviation Turbines Kerosene',                         'department'],
            ['CAT-REV', '41000-402',          'Marine Gas Oil - MGO',                               'department'],
            ['CAT-REV', '41000-404',          'LPG',                                                'department'],
            ['CAT-REV', '41000-407',          'AGO Rig',                                            'department'],
            ['CAT-REV', '41000-408',          'Ocean Bunkering',                                    'department'],
            ['CAT-REV', '41000-500',          'CRM/LPG',                                            'department'],
            ['CAT-REV', '41000-411-002-03',   'Diesel Gamma SAE 40 - 4 Litres',                    'department'],
            ['CAT-REV', '41000-411-012-01',   'GOIL Engine Oil 40/50 - 208 Litres',                'department'],
            ['CAT-REV', '41000-411-017-03',   'Motor Oil HD 40/50 - 4 Litres',                     'department'],
            ['CAT-REV', '41000-414-007-01',   'Marinemix GM - 1 Litre',                            'department'],
            ['CAT-REV', '41000-411-017-01',   'Motor Oil HD 40/50 - 1 Litre',                      'department'],
            ['CAT-REV', '41000-411-002-01',   'Diesel Gamma SAE 40 - 1 Litre',                     'department'],
            ['CAT-REV', '41000-411-019-03',   'Super Motor Oil 20W/50 - 4 Litres',                 'department'],
            ['CAT-REV', '41000-411-026-01',   'Transformer Oil - 210 Litres',                      'department'],
            ['CAT-REV', '41000-411-002-02',   'Diesel Gamma SAE 40 - 208 Litres',                  'department'],
            ['CAT-REV', '41000-411-021-01',   'Super Turbo 15W/40 - 208 Litres',                   'department'],
            ['CAT-REV', '41000-411-002-05',   'Diesel Gamma SAE 40 - 5 Litres',                    'department'],
            ['CAT-REV', '41000-413-020-01',   'OSO 68 - 208 Litres',                               'department'],
            ['CAT-REV', '41000-411-020-08',   'Super Synthetic 5W/30 - 5 Litres',                  'department'],
            ['CAT-REV', '41000-411-020-12',   'Super Synthetic 5W/20 - 5 Litres',                  'department'],
            ['CAT-REV', '41000-411-027-01',   'GOIL Super Synthetic 0W20 - 5 Litres',              'department'],
            ['CAT-REV', '41000-411-016-02',   'GOIL Super Taxi SAE 40 - 4 Litres',                 'department'],
            ['CAT-REV', '41000-411-020-11',   'Super Synthetic 5W/20 - 4 Litres',                  'department'],
            ['CAT-REV', '41000-411-020-17',   'Super Synthetic 5W/30 - 4 Litres',                  'department'],
            ['CAT-REV', '41000-416-003-01',   'Rotra HY 140 - 208 Litres',                         'department'],
            ['CAT-REV', '41000-411-002-04',   'Diesel Gamma SAE 40 - 20 Litres',                   'department'],
            ['CAT-REV', '41000-411-008-04',   'Diesel Synthetic SAE 15W/40 - 5 Litres',            'department'],
            ['CAT-REV', '41000-416-001-01',   'ATF Dexron II - 1 Litre',                           'department'],
            ['CAT-REV', '41000-415-002-01',   'Radiator Coolant Solution - 1 Litre',               'department'],
            ['CAT-REV', '41000-411-020-05',   'Super Synthetic 5W/40 - 5 Litres',                  'department'],
            ['CAT-REV', '41000-411-020-02',   'Super Synthetic 5W/40 - 4 Litres',                  'department'],
            ['CAT-REV', '41000-413-015-02',   'OSO 100 - 209 Litres',                              'department'],
            ['CAT-REV', '41000-411-023-01',   'Sigma Truck 15W/40 - 209 Litres',                   'department'],
            ['CAT-REV', '41000-413-020-02',   'OSO 68 - 209 Litres',                               'department'],
            ['CAT-REV', '41000-413-057-03',   'Transmission Fluid TO-4 SAE 30 - 209 Litres',       'department'],
            ['CAT-REV', '41000-413-030-04',   'Transmission Fluid TO-4 SAE 50 - 209 Litres',       'department'],
            ['CAT-REV', '41000-413-046-01',   'Deg. Compound - 200 Litres',                        'department'],
            ['CAT-REV', '41000-413-058-02',   'Rockdrill Oil 320 - 208 Litres',                    'department'],
            ['CAT-REV', '41000-413-004-02',   'Blasia 220 - 209 Litres',                           'department'],
            ['CAT-REV', '41000-413-024-02',   'Therm Oil 7 - 209 Litres',                          'department'],
            ['CAT-REV', '41000-412-014-01',   'Renolit Moly LX 2 - 180 kg',                        'department'],

            // ── Other Income ──────────────────────────────────────────────────
            ['CAT-OTH-INC', '42114', 'Rent Income - Restaurants',                                  'department'],
            ['CAT-OTH-INC', '42129', 'ATM Rent',                                                   'department'],
            ['CAT-OTH-INC', '42122', 'Service Station Rental Income',                              'department'],

            // ── Cost of Sales (52000 series) ──────────────────────────────────
            ['CAT-COS', '52000-113',          'Diesel XP',                                          'department'],
            ['CAT-COS', '52000-114',          'Super XP RON95',                                     'department'],
            ['CAT-COS', '52000-120',          'Super XP RON91',                                     'department'],
            ['CAT-COS', '52000-115',          'Mining Diesel',                                      'department'],
            ['CAT-COS', '52000-116',          'Kerosene',                                           'department'],
            ['CAT-COS', '52000-117',          'Fuel Oil (RFO)',                                     'department'],
            ['CAT-COS', '52000-118',          'Premix',                                             'department'],
            ['CAT-COS', '52000-401',          'Aviation Turbines Kerosene',                         'department'],
            ['CAT-COS', '52000-402',          'Marine Gas Oil - MGO',                               'department'],
            ['CAT-COS', '52000-404',          'LPG',                                                'department'],
            ['CAT-COS', '52000-407',          'AGO Rig',                                            'department'],
            ['CAT-COS', '52000-408',          'Ocean Bunkering',                                    'department'],
            ['CAT-COS', '52000-500',          'CRM/LPG',                                            'department'],
            ['CAT-COS', '52000-411-002-03',   'Diesel Gamma SAE 40 - 4 Litres',                    'department'],
            ['CAT-COS', '52000-411-012-01',   'GOIL Engine Oil 40/50 - 208 Litres',                'department'],
            ['CAT-COS', '52000-411-017-03',   'Motor Oil HD 40/50 - 4 Litres',                     'department'],
            ['CAT-COS', '52000-414-007-01',   'Marinemix GM - 1 Litre',                            'department'],
            ['CAT-COS', '52000-411-017-01',   'Motor Oil HD 40/50 - 1 Litre',                      'department'],
            ['CAT-COS', '52000-411-002-01',   'Diesel Gamma SAE 40 - 1 Litre',                     'department'],
            ['CAT-COS', '52000-411-019-03',   'Super Motor Oil 20W/50 - 4 Litres',                 'department'],
            ['CAT-COS', '52000-411-026-01',   'Transformer Oil - 210 Litres',                      'department'],
            ['CAT-COS', '52000-411-002-02',   'Diesel Gamma SAE 40 - 208 Litres',                  'department'],
            ['CAT-COS', '52000-411-021-01',   'Super Turbo 15W/40 - 208 Litres',                   'department'],
            ['CAT-COS', '52000-411-002-05',   'Diesel Gamma SAE 40 - 5 Litres',                    'department'],
            ['CAT-COS', '52000-413-020-01',   'OSO 68 - 208 Litres',                               'department'],
            ['CAT-COS', '52000-411-020-08',   'Super Synthetic 5W/30 - 5 Litres',                  'department'],
            ['CAT-COS', '52000-411-020-12',   'Super Synthetic 5W/20 - 5 Litres',                  'department'],
            ['CAT-COS', '52000-411-027-01',   'GOIL Super Synthetic 0W20 - 5 Litres',              'department'],
            ['CAT-COS', '52000-411-016-02',   'GOIL Super Taxi SAE 40 - 4 Litres',                 'department'],
            ['CAT-COS', '52000-411-020-11',   'Super Synthetic 5W/20 - 4 Litres',                  'department'],
            ['CAT-COS', '52000-411-020-17',   'Super Synthetic 5W/30 - 4 Litres',                  'department'],
            ['CAT-COS', '52000-416-003-01',   'Rotra HY 140 - 208 Litres',                         'department'],
            ['CAT-COS', '52000-411-002-04',   'Diesel Gamma SAE 40 - 20 Litres',                   'department'],
            ['CAT-COS', '52000-411-008-04',   'Diesel Synthetic SAE 15W/40 - 5 Litres',            'department'],
            ['CAT-COS', '52000-416-001-01',   'ATF Dexron II - 1 Litre',                           'department'],
            ['CAT-COS', '52000-415-002-01',   'Radiator Coolant Solution - 1 Litre',               'department'],
            ['CAT-COS', '52000-411-020-05',   'Super Synthetic 5W/40 - 5 Litres',                  'department'],
            ['CAT-COS', '52000-411-020-02',   'Super Synthetic 5W/40 - 4 Litres',                  'department'],
            ['CAT-COS', '52000-413-015-02',   'OSO 100 - 209 Litres',                              'department'],
            ['CAT-COS', '52000-411-023-01',   'Sigma Truck 15W/40 - 209 Litres',                   'department'],
            ['CAT-COS', '52000-413-020-02',   'OSO 68 - 209 Litres',                               'department'],
            ['CAT-COS', '52000-413-057-03',   'Transmission Fluid TO-4 SAE 30 - 209 Litres',       'department'],
            ['CAT-COS', '52000-413-030-04',   'Transmission Fluid TO-4 SAE 50 - 209 Litres',       'department'],
            ['CAT-COS', '52000-413-046-01',   'Deg. Compound - 200 Litres',                        'department'],
            ['CAT-COS', '52000-413-058-02',   'Rockdrill Oil 320 - 208 Litres',                    'department'],
            ['CAT-COS', '52000-413-004-02',   'Blasia 220 - 209 Litres',                           'department'],
            ['CAT-COS', '52000-413-024-02',   'Therm Oil 7 - 209 Litres',                          'department'],
            ['CAT-COS', '52000-412-014-01',   'Renolit Moly LX 2 - 180 kg',                        'department'],
        ];

        foreach ($codes as [$catCode, $code, $name, $scope]) {
            $catId = $catMap[$catCode] ?? null;
            if (! $catId) {
                $this->command->warn("  Category not found: {$catCode} — skipping {$code}");
                continue;
            }
            DB::table('account_codes')->updateOrInsert(
                ['code' => $code, 'scope' => $scope],
                [
                    'code'                => $code,
                    'name'                => $name,
                    'account_category_id' => $catId,
                    'scope'               => $scope,
                    'is_active'           => true,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]
            );
        }

        $this->command->info('  ' . count($codes) . ' account codes done.');

        // ── 4. Department ↔ Account Code assignments ──────────────────────────
        $this->command->info('Seeding department–code assignments…');

        // [account_code, dept_code]  — valid dept codes only (from rev codes new.xlsx)
        $assignments = [
            ['61101', '02001'],
            ['61201', '05001'], ['61202', '05001'], ['61203', '05001'],
            ['61301', '01004'], ['61301', '05001'],
            ['61302', '01004'], ['61303', '01004'], ['61304', '01004'],
            ['61400', '02001'],
            ['61411', '02001'],
            ['61412', '02001'],
            ['61413', '02001'],
            ['61420', '02001'],
            ['61434', '02001'], ['61435', '02001'],
            ['61437', '02001'], ['61438', '02001'],
            ['61439', '02001'],
            ['61440', '01001'], ['61440', '01002'], ['61440', '01003'], ['61440', '01004'], ['61440', '01005'],
            ['61440', '01006'], ['61440', '01007'], ['61440', '01008'],
            ['61440', '02001'], ['61440', '03001'], ['61440', '04001'], ['61440', '05001'], ['61440', '06001'],
            ['61440', '07000'], ['61440', '08001'],
            ['61450', '02001'],
            ['61501', '07000'], ['61502', '07000'], ['61503', '07000'], ['61504', '07000'],
            ['61505', '01004'], ['61505', '07000'],
            ['61506', '07000'], ['61507', '07000'],
            ['61601', '08001'],
            ['61603', '06001'],
            ['61604', '02001'],
            ['61605', '01001'], ['61605', '01002'], ['61605', '01003'], ['61605', '01004'], ['61605', '01005'],
            ['61605', '01006'], ['61605', '01007'], ['61605', '01008'],
            ['61605', '02001'], ['61605', '03001'], ['61605', '04001'], ['61605', '05001'], ['61605', '06001'],
            ['61605', '07000'], ['61605', '08001'],
            ['61606', '02001'],
            ['61607', '06001'],
            ['61609', '07000'],
            ['61801', '01001'], ['61801', '01002'], ['61801', '01003'], ['61801', '01004'], ['61801', '01005'],
            ['61801', '01006'], ['61801', '01007'], ['61801', '01008'],
            ['61801', '02001'], ['61801', '03001'], ['61801', '04001'], ['61801', '05001'], ['61801', '06001'],
            ['61801', '07000'], ['61801', '08001'],
            ['61802', '01003'],
            ['61803', '01001'], ['61803', '01002'], ['61803', '01003'], ['61803', '01004'], ['61803', '01005'],
            ['61803', '01006'], ['61803', '01007'], ['61803', '01008'],
            ['61803', '02001'], ['61803', '03001'], ['61803', '04001'], ['61803', '05001'], ['61803', '06001'],
            ['61803', '07000'], ['61803', '08001'],
            ['61805', '01003'], ['61805', '04001'],
            ['61806', '02001'],
            ['61807', '03001'], ['61807', '04001'], ['61807', '05001'],
            ['61808', '05001'],
            ['61809', '02001'],
            ['61810', '04001'],
            ['61811', '03001'],
            ['61812', '01007'], ['61812', '03001'],
            ['61813', '08001'],
            ['61814', '01001'], ['61814', '01002'], ['61814', '01003'], ['61814', '01004'], ['61814', '01005'],
            ['61814', '01006'], ['61814', '01007'], ['61814', '01008'],
            ['61814', '02001'], ['61814', '03001'], ['61814', '04001'], ['61814', '05001'], ['61814', '06001'],
            ['61814', '07000'], ['61814', '08001'],
            ['61815', '03001'], ['61815', '04001'],
            ['62615', '04001'],
            ['62101', '07000'], ['62102', '07000'], ['62103', '07000'], ['62104', '07000'],
            ['62105', '01004'],
            ['62106', '07000'], ['62107', '07000'], ['62108', '07000'],
            ['62201', '06001'], ['62202', '06001'],
            ['62203', '01001'], ['62203', '01002'], ['62203', '01003'], ['62203', '01004'], ['62203', '01005'],
            ['62203', '01006'], ['62203', '01007'], ['62203', '01008'],
            ['62203', '02001'], ['62203', '03001'], ['62203', '04001'], ['62203', '05001'], ['62203', '06001'],
            ['62203', '07000'], ['62203', '08001'],
            ['62301', '05001'], ['62302', '05001'], ['62303', '05001'], ['62304', '05001'],
            ['62305', '05001'], ['62306', '05001'], ['62310', '05001'],
            ['62401', '01004'], ['62402', '01004'], ['62403', '01004'], ['62404', '01004'],
            ['62405', '01004'], ['62406', '01004'], ['62407', '01004'],
            ['62501', '01003'], ['62502', '01003'],
            ['62601', '07000'], ['62602', '07000'], ['62603', '07000'], ['62604', '07000'], ['62605', '07000'],
            ['62607', '07000'], ['62608', '07000'], ['62609', '07000'], ['62610', '07000'],
            ['62611', '07000'],
            ['62612', '01001'], ['62612', '01002'], ['62612', '01003'], ['62612', '01004'], ['62612', '01005'],
            ['62612', '01006'], ['62612', '01007'], ['62612', '01008'],
            ['62612', '02001'], ['62612', '03001'], ['62612', '04001'], ['62612', '05001'], ['62612', '06001'],
            ['62612', '07000'], ['62612', '08001'],
            ['62613', '01001'], ['62613', '01002'], ['62613', '01003'], ['62613', '01004'], ['62613', '01005'],
            ['62613', '01006'], ['62613', '01007'], ['62613', '01008'],
            ['62613', '02001'], ['62613', '03001'], ['62613', '04001'], ['62613', '05001'], ['62613', '06001'],
            ['62613', '07000'], ['62613', '08001'],
            ['62614', '07000'],
            ['62618', '01001'], ['62618', '01002'], ['62618', '01003'], ['62618', '05001'], ['62618', '07000'],
            ['62615', '01003'], ['62615', '03001'], ['62615', '07000'],
            ['62616', '01001'], ['62616', '01002'], ['62616', '01003'], ['62616', '01004'], ['62616', '01005'],
            ['62616', '01006'], ['62616', '01007'], ['62616', '01008'],
            ['62616', '02001'], ['62616', '03001'], ['62616', '04001'], ['62616', '05001'], ['62616', '06001'],
            ['62616', '07000'], ['62616', '08001'],
            ['62617', '07000'],
            ['15299', '07000'],
            ['62620', '01003'], ['62620', '07000'],
            ['62701', '01001'], ['62701', '05001'], ['62701', '08001'],
            ['62702', '05001'],
            ['62703', '07000'],
            ['62704', '01001'], ['62704', '01006'],
            ['62710', '01001'],
            ['62711', '01001'], ['62712', '01001'],
            ['62619', '01006'],
            ['62721', '01001'], ['62721', '01006'],
            ['62722', '02001'],
            ['62723', '07000'],
            ['62724', '01001'], ['62724', '01002'], ['62724', '01003'], ['62724', '01004'], ['62724', '01005'],
            ['62724', '01006'], ['62724', '01007'], ['62724', '01008'],
            ['62724', '02001'], ['62724', '03001'], ['62724', '04001'], ['62724', '05001'], ['62724', '06001'],
            ['62724', '07000'], ['62724', '08001'],
            ['62725', '01001'], ['62725', '01002'], ['62725', '01003'], ['62725', '01004'], ['62725', '01005'],
            ['62725', '01006'], ['62725', '01007'], ['62725', '01008'],
            ['62725', '02001'], ['62725', '03001'], ['62725', '04001'], ['62725', '05001'], ['62725', '06001'],
            ['62725', '07000'], ['62725', '08001'],
            ['62726', '01003'],
            ['62727', '01001'], ['62727', '01002'], ['62727', '01003'], ['62727', '01004'], ['62727', '01005'],
            ['62727', '01006'], ['62727', '01007'], ['62727', '01008'],
            ['62727', '02001'], ['62727', '03001'], ['62727', '04001'], ['62727', '05001'], ['62727', '06001'],
            ['62727', '07000'], ['62727', '08001'],
            ['62728', '01001'], ['62728', '01006'],
            ['62740', '02001'], ['62741', '02001'],
            ['62751', '02001'], ['62752', '02001'], ['62753', '02001'], ['62754', '02001'],
            ['62760', '02001'],
            ['62761', '06001'],
            ['62771', '03001'], ['62771', '04001'],
            ['62772', '01001'], ['62772', '01002'], ['62772', '01003'], ['62772', '01004'], ['62772', '01005'],
            ['62772', '01006'], ['62772', '01007'], ['62772', '01008'],
            ['62772', '02001'], ['62772', '03001'], ['62772', '04001'], ['62772', '05001'], ['62772', '06001'],
            ['62772', '07000'], ['62772', '08001'],
            ['62773', '01001'], ['62773', '01002'], ['62773', '01003'], ['62773', '01004'], ['62773', '01005'],
            ['62773', '01006'], ['62773', '01007'], ['62773', '01008'],
            ['62773', '02001'], ['62773', '03001'], ['62773', '04001'], ['62773', '05001'], ['62773', '06001'],
            ['62773', '07000'], ['62773', '08001'],
            ['62774', '01001'], ['62774', '01002'], ['62774', '01003'], ['62774', '01004'], ['62774', '01005'],
            ['62774', '01006'], ['62774', '01007'], ['62774', '01008'],
            ['62774', '02001'], ['62774', '03001'], ['62774', '04001'], ['62774', '05001'], ['62774', '06001'],
            ['62774', '07000'], ['62774', '08001'],
            ['62776', '07000'],
            ['62777', '06001'],
            ['62778', '01001'], ['62778', '01002'], ['62778', '01003'], ['62778', '01004'], ['62778', '01005'],
            ['62778', '01006'], ['62778', '01007'], ['62778', '01008'],
            ['62778', '02001'], ['62778', '03001'], ['62778', '04001'], ['62778', '05001'], ['62778', '06001'],
            ['62778', '07000'], ['62778', '08001'],
            ['62779', '01001'], ['62779', '01002'], ['62779', '01003'], ['62779', '01004'], ['62779', '01005'],
            ['62779', '01006'], ['62779', '01007'], ['62779', '01008'],
            ['62779', '02001'], ['62779', '03001'], ['62779', '04001'], ['62779', '05001'], ['62779', '06001'],
            ['62779', '07000'], ['62779', '08001'],
            ['62780', '02001'],
            ['62781', '05001'], ['62782', '05001'], ['62783', '05001'],
            ['62784', '04001'], ['62784', '05001'],
            ['62999', '07000'],
            ['63000', '05001'], ['63101', '05001'], ['63102', '05001'], ['63103', '05001'],
            ['63104', '05001'], ['63105', '05001'], ['63999', '05001'],

            // ── Housing Rent for Personnel (62785) — all applicable depts ─────
            ['62785', '01001'], ['62785', '01002'], ['62785', '01003'], ['62785', '01004'],
            ['62785', '01005'], ['62785', '02001'], ['62785', '03001'],
            ['62785', '04001'], ['62785', '05001'], ['62785', '06001'], ['62785', '07000'],
            ['62785', '08001'],

            // ── Contingency ───────────────────────────────────────────────────
            ['99999', '01001'], ['99999', '15000'],

            // ── COO (15000) ───────────────────────────────────────────────────
            ['61440', '15000'], ['61605', '15000'], ['61801', '15000'], ['61803', '15000'],
            ['61814', '15000'], ['62203', '15000'], ['62612', '15000'], ['62613', '15000'],
            ['62616', '15000'], ['62618', '15000'], ['62701', '15000'], ['62704', '15000'],
            ['62710', '15000'], ['62711', '15000'], ['62712', '15000'], ['62721', '15000'],
            ['62724', '15000'], ['62725', '15000'], ['62727', '15000'], ['62728', '15000'],
            ['62772', '15000'], ['62773', '15000'], ['62774', '15000'], ['62778', '15000'],
            ['62779', '15000'], ['62785', '15000'],

            // ── Estates (18000) ───────────────────────────────────────────────
            ['61440', '18000'], ['61602', '18000'], ['61605', '18000'], ['61606', '18000'],
            ['61608', '18000'], ['61610', '18000'], ['61801', '18000'], ['61803', '18000'],
            ['61804', '18000'], ['61814', '18000'], ['62203', '18000'], ['62612', '18000'],
            ['62613', '18000'], ['62616', '18000'], ['62724', '18000'], ['62725', '18000'],
            ['62727', '18000'], ['62771', '18000'], ['62772', '18000'], ['62773', '18000'],
            ['62774', '18000'], ['62775', '18000'], ['62778', '18000'], ['62779', '18000'],
            ['62785', '18000'],

            // ── Technical Services (16000) ────────────────────────────────────
            ['61101', '16000'], ['61400', '16000'], ['61411', '16000'], ['61412', '16000'],
            ['61413', '16000'], ['61420', '16000'], ['61431', '16000'], ['61432', '16000'],
            ['61433', '16000'], ['61436', '16000'], ['61439', '16000'], ['61440', '16000'],
            ['61445', '16000'], ['61450', '16000'], ['61605', '16000'], ['61801', '16000'],
            ['61803', '16000'], ['61807', '16000'], ['61814', '16000'], ['62203', '16000'],
            ['62612', '16000'], ['62613', '16000'], ['62615', '16000'], ['62616', '16000'],
            ['62724', '16000'], ['62725', '16000'], ['62727', '16000'], ['62772', '16000'],
            ['62773', '16000'], ['62774', '16000'], ['62778', '16000'], ['62779', '16000'],
            ['62785', '16000'],

            // ── Non Fuels (17000) ─────────────────────────────────────────────
            ['61440', '17000'], ['61605', '17000'], ['61801', '17000'], ['61803', '17000'],
            ['61814', '17000'], ['62203', '17000'], ['62612', '17000'], ['62613', '17000'],
            ['62615', '17000'], ['62616', '17000'], ['62724', '17000'], ['62725', '17000'],
            ['62727', '17000'], ['62772', '17000'], ['62773', '17000'], ['62774', '17000'],
            ['62778', '17000'], ['62779', '17000'], ['62785', '17000'],
        ];

        $deptMap = DB::table('departments')->pluck('id', 'code')->all();
        $codeMap = DB::table('account_codes')->where('scope', 'department')->pluck('id', 'code')->all();

        $inserted = 0;
        foreach ($assignments as [$accCode, $deptCode]) {
            $deptId = $deptMap[$deptCode] ?? null;
            $codeId = $codeMap[$accCode] ?? null;
            if (! $deptId || ! $codeId) {
                $this->command->warn("  Skipping {$accCode} → {$deptCode}: not found");
                continue;
            }
            DB::table('department_account_codes')->updateOrInsert(
                ['department_id' => $deptId, 'account_code_id' => $codeId],
                ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
            $inserted++;
        }

        $this->command->info("  {$inserted} department–code assignments done.");

        // ── Revenue / Cost of Sales assignments ───────────────────────────────
        $this->command->info('Seeding revenue & cost of sales assignments…');

        $fuelsMktCodes = [
            '41000-113', '41000-114', '41000-120',
            '52000-113', '52000-114', '52000-120',
        ];

        $allRevCosCodes = DB::table('account_codes')
            ->whereIn('account_category_id', DB::table('account_categories')
                ->whereIn('code', ['CAT-REV', 'CAT-COS', 'CAT-OTH-INC'])
                ->pluck('id'))
            ->pluck('code')
            ->all();

        $tspmCodes = array_values(array_diff($allRevCosCodes, $fuelsMktCodes));

        $revCosInserted = 0;
        foreach ($fuelsMktCodes as $code) {
            $deptId = $deptMap['04001'] ?? null;
            $codeId = $codeMap[$code]   ?? null;
            if ($deptId && $codeId) {
                DB::table('department_account_codes')->updateOrInsert(
                    ['department_id' => $deptId, 'account_code_id' => $codeId],
                    ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
                );
                $revCosInserted++;
            }
        }
        foreach ($tspmCodes as $code) {
            $deptId = $deptMap['03001'] ?? null;
            $codeId = $codeMap[$code]   ?? null;
            if ($deptId && $codeId) {
                DB::table('department_account_codes')->updateOrInsert(
                    ['department_id' => $deptId, 'account_code_id' => $codeId],
                    ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
                );
                $revCosInserted++;
            }
        }

        $this->command->info("  {$revCosInserted} revenue & COS assignments done.");

        // ── 5. Approval Stages ────────────────────────────────────────────────
        $this->command->info('Seeding approval stages…');

        // route_key: null = always runs, 'gceo' / 'coo' = branch-specific stage
        $stages = [
            ['name' => 'Department Head',    'order' => 1, 'role_name' => 'department_head',  'route_key' => null],
            ['name' => 'Finance Review',     'order' => 2, 'role_name' => 'finance_reviewer', 'route_key' => null],
            ['name' => 'GCEO & MD Approval', 'order' => 3, 'role_name' => 'gceo',             'route_key' => 'gceo'],
            ['name' => 'COO Approval',       'order' => 4, 'role_name' => 'coo',              'route_key' => 'coo'],
            ['name' => 'Board Approval',     'order' => 5, 'role_name' => 'board',            'route_key' => null],
        ];

        foreach ($stages as $stage) {
            DB::table('approval_stages')->updateOrInsert(
                ['role_name' => $stage['role_name']],
                array_merge($stage, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // Move the old Board row (order=4) to order=5 if it still exists at that position
        DB::table('approval_stages')
            ->where('role_name', 'board')
            ->update(['order' => 5, 'route_key' => null]);

        $this->command->info('  ' . count($stages) . ' approval stages done.');

        // ── 6. Sub-entry templates ────────────────────────────────────────────
        $this->command->info('Seeding sub-entry templates…');

        $subTemplates = [
            // 61814 – Business Promotion
            '61814' => [
                'Contingency',
                'Promotional Activities (Targeted Promos, discounts etc)',
                'Marketing Conferences',
                'Event Launches & Station Commissions',
            ],
            // 62704 – CSI
            '62704' => [
                'Community Support - Impact projects',
                'Health and Safety',
                'Community Support - Education',
                'Community Support - Capacity building',
                'Environmental Protection and Sustainability',
                'Heritage Support',
                'Stakeholder Relation',
                'Branded items, Events',
            ],
        ];

        $subCount = 0;
        foreach ($subTemplates as $accCode => $names) {
            $codeId = $codeMap[$accCode] ?? null;
            if (! $codeId) {
                $this->command->warn("  Sub-templates: account code {$accCode} not found, skipping.");
                continue;
            }
            // Remove existing templates for this code so re-runs don't duplicate
            DB::table('account_code_sub_entry_templates')->where('account_code_id', $codeId)->delete();
            foreach ($names as $order => $name) {
                DB::table('account_code_sub_entry_templates')->insert([
                    'account_code_id' => $codeId,
                    'name'            => $name,
                    'sort_order'      => $order + 1,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                $subCount++;
            }
        }

        $this->command->info("  {$subCount} sub-entry templates done.");

        $this->command->newLine();
        $this->command->info('✓  GOIL data loaded successfully.');
        $this->command->info('   Next: open a Budget Period.');
    }
}
