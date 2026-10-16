<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountCode;
use App\Models\AccountCategory;

class DepartmentAccountCodesSeeder extends Seeder
{
    public function run(): void
    {
        $cats = AccountCategory::pluck('id', 'code');

        // [code, name, category_code]
        $codes = [

            // ── 1. Salaries and Allowances ───────────────────────────────────────
            ['62608', 'Normal Salaries & Wages',                           'CAT-01'],
            ['62609', 'Overtime',                                          'CAT-01'],
            ['62610', 'SSF Employer\'s Contribution',                      'CAT-01'],
            ['62603', 'Employer Tier 3 Contri',                            'CAT-01'],
            ['62602', 'Early Retirement',                                  'CAT-01'],
            ['62601', 'Annual Bonus',                                      'CAT-01'],
            ['62604', 'Staff Cost - Month 13',                             'CAT-01'],
            ['62612', 'Subscriptions to Professional Bodies',              'CAT-01'],
            ['15299', 'Staff Loans Recovery Clearing Account',             'CAT-01'],

            // ── 2. Travelling & Transport ────────────────────────────────────────
            ['62614', 'Transfers & Relocation Allowances',                 'CAT-02'],
            ['62778', 'Transport Expenses',                                'CAT-02'],
            ['62779', 'Training (Overseas)',                               'CAT-02'],

            // ── 3. Other Personnel Cost ──────────────────────────────────────────
            ['61805', 'Training - Frontline',                              'CAT-03'],
            ['62618', 'Seasonal End of Year Activities',                   'CAT-03'],
            ['62607', 'Medical',                                           'CAT-03'],
            ['62611', 'Staff Welfare',                                     'CAT-03'],
            ['62613', 'Training - Local',                                  'CAT-03'],
            ['62615', 'Uniforms',                                          'CAT-03'],
            ['62616', 'Staff Cost - Misc.',                                'CAT-03'],
            ['62617', 'Staff Cost - Retired Staff Medicals',               'CAT-03'],
            ['62619', 'Scholarship',                                       'CAT-03'],
            ['62620', 'Staff Cost - Medical Screening',                    'CAT-03'],
            ['62723', 'Canteen',                                           'CAT-03'],
            ['62724', 'Fitness & Recreational Assistance',                 'CAT-03'],
            ['62725', 'Hotel & Outstation Expenses',                       'CAT-03'],

            // ── 4. Various Materials ─────────────────────────────────────────────
            ['61603', 'Coupon Production',                                 'CAT-04'],
            ['61607', 'POS/GoCard Expenses',                               'CAT-04'],
            ['62202', 'Computing Expenses - Forms & Accessories',          'CAT-04'],
            ['62773', 'Printing & Stationery',                             'CAT-04'],

            // ── 5. Electricity & Water ───────────────────────────────────────────
            ['61604', 'Electricity for Depots',                            'CAT-05'],
            ['61806', 'Water for Depots',                                  'CAT-05'],
            ['62722', 'Electricity - Offices & Houses',                    'CAT-05'],
            ['62780', 'Water - Offices & Houses',                          'CAT-05'],

            // ── 6. Maintenance & Running Cost ────────────────────────────────────
            ['61400', 'Maintenance of Stations',                           'CAT-06'],
            ['61411', 'Genset Maint. & Repairs',                           'CAT-06'],
            ['61412', 'Pump Maint. & Repairs',                             'CAT-06'],
            ['61413', 'General Maint. & Repairs',                          'CAT-06'],
            ['61420', 'Maintenance of Consumer Installations',             'CAT-06'],
            ['61431', 'Maint. of Aviation Depot - Accra',                  'CAT-06'],
            ['61432', 'Maint. of Aviation Depot - Kumasi',                 'CAT-06'],
            ['61433', 'Maint. of Aviation Depot - Takoradi',               'CAT-06'],
            ['61445', 'Maint. of Aviation Depot - Tamale',                 'CAT-06'],
            ['61434', 'Maint. of LPG & Lubes Depot - Kumasi',              'CAT-06'],
            ['61435', 'Maint. of LPG & Lubes Depot - Tema',                'CAT-06'],
            ['61436', 'Maint. of Bunkering - Sekondi Naval Base & T\'adi Harbour', 'CAT-06'],
            ['61437', 'Maint. of Lubes Depot - Takoradi',                  'CAT-06'],
            ['61438', 'Maint. of Lubes Depot - Tamale',                    'CAT-06'],
            ['61439', 'Maint. of Installations',                           'CAT-06'],
            ['61440', 'Furniture and Office Equipment',                    'CAT-06'],
            ['61450', 'Maintenance of Plant Machinery & Equipment',        'CAT-06'],
            ['61501', 'Revenue Generating Vehicles - Cost of Labour Charges', 'CAT-06'],
            ['61502', 'Revenue Generating Vehicles - Cost of Spares',      'CAT-06'],
            ['61503', 'Revenue Generating Vehicles - Cost of Tyres',       'CAT-06'],
            ['61504', 'Revenue Generating Vehicles - Insurance',           'CAT-06'],
            ['61505', 'Revenue Generating Vehicles - Fuels',               'CAT-06'],
            ['61506', 'Revenue Generating Vehicles - Lubes',               'CAT-06'],
            ['61507', 'Revenue Generating Vehicles - Permits & Licensing', 'CAT-06'],
            ['61610', 'Rent - Administration',                             'CAT-06'],
            ['61809', 'Aro Warehouse Maintenance',                         'CAT-06'],
            ['61810', 'Take Over Stations Commissions',                    'CAT-06'],
            ['61811', 'Lubes Production / Business Development',           'CAT-06'],
            ['61812', 'Clearing & Handling Charges',                       'CAT-06'],
            ['62101', 'Administration Vehicles - Cost of Labour Charges & Spares', 'CAT-06'],
            ['62102', 'Administration Vehicles - Cost of Spares',          'CAT-06'],
            ['62103', 'Administration Vehicles - Cost of Tyres',           'CAT-06'],
            ['62104', 'Administration Vehicles - Fuels Internal Consumption', 'CAT-06'],
            ['62106', 'Administration Vehicles - Lubes',                   'CAT-06'],
            ['62201', 'Computing Expenses - Computer Software & Network Maintenance', 'CAT-06'],
            ['62501', 'HSSEQ Expenses',                                    'CAT-06'],
            ['62740', 'Maintenance of Office',                             'CAT-06'],
            ['62741', 'Maintenance of Gensets - Offices',                  'CAT-06'],
            ['62751', 'Maint. of Guest House',                             'CAT-06'],
            ['62752', 'Maint. of Residential Accommodation',               'CAT-06'],
            ['62753', 'Maint. of Head Office',                             'CAT-06'],
            ['62754', 'Maint. - Other Residences',                         'CAT-06'],
            ['62760', 'Maintenance of Office Equipment',                   'CAT-06'],
            ['62761', 'Coupons Replacement',                               'CAT-06'],
            ['62777', 'Software License',                                  'CAT-06'],
            ['62781', 'Stock Losses',                                      'CAT-06'],
            ['62782', 'Damaged Stocks',                                    'CAT-06'],
            ['62783', 'Debt Recovery',                                     'CAT-06'],
            ['62784', 'Loyalty Award',                                     'CAT-06'],
            ['62785', 'Housing Rent for Personnel',                        'CAT-06'],

            // ── 7. Various Services ──────────────────────────────────────────────
            ['62502', 'Security Services',                                 'CAT-07'],
            ['62703', 'Contract Labour',                                   'CAT-07'],
            ['62776', 'Cleaning Services',                                 'CAT-07'],

            // ── 9. Professional Service ──────────────────────────────────────────
            ['61101', 'Calibration of U/G Tanks',                          'CAT-09'],
            ['61802', 'Standard Boards & Statutory',                       'CAT-09'],
            ['61804', 'Survey Fees',                                       'CAT-09'],
            ['62701', 'Annual General Meeting',                            'CAT-09'],
            ['62702', 'Audit Fees',                                        'CAT-09'],
            ['62711', 'Directors Remuneration',                            'CAT-09'],
            ['62712', 'Directors Expenses',                                'CAT-09'],
            ['62774', 'Professional Services Fees & Charges',              'CAT-09'],

            // ── 10. Taxes / Levies / Permits ─────────────────────────────────────
            ['61608', 'Ground Rent & Parking Lot',                         'CAT-10'],
            ['61609', 'Motorway Toll - Revenue Generating Vehicles',       'CAT-10'],
            ['62107', 'Administration Vehicles - Permits & Licenses',      'CAT-10'],
            ['62108', 'Administration Vehicles - Motorway Tolls',          'CAT-10'],
            ['62771', 'Permit & Licenses',                                 'CAT-10'],
            ['62775', 'Property Rate',                                     'CAT-10'],

            // ── 11. Equipment Hiring & Rental ────────────────────────────────────
            ['61605', 'Equipment Hiring',                                  'CAT-11'],
            ['61606', 'General Rates on Buildings - Depots & Stations',    'CAT-11'],

            // ── 12. Stations Land Leasing ────────────────────────────────────────
            ['61602', 'Annual Lease',                                      'CAT-12'],

            // ── 13. Insurance Premium ────────────────────────────────────────────
            ['61301', 'Insurance - Depots / Depreciation - Plant',         'CAT-13'],
            ['61302', 'Insurance - Motor Vehicles',                        'CAT-13'],
            ['61303', 'Insurance - Plant Machinery & Equipment',           'CAT-13'],
            ['61304', 'Insurance - Stations Company Owned',                'CAT-13'],
            ['62105', 'Administration Vehicles - Insurance',               'CAT-13'],
            ['62401', 'Insurance - Head Office',                           'CAT-13'],
            ['62402', 'Insurance - Residences',                            'CAT-13'],
            ['62403', 'Insurance - Goods in Transit',                      'CAT-13'],
            ['62404', 'Insurance - Fidelity Guarantee',                    'CAT-13'],
            ['62405', 'Insurance - Public Liability',                      'CAT-13'],
            ['62406', 'Insurance - Burglary',                              'CAT-13'],
            ['62407', 'Assets All Risk',                                   'CAT-13'],
            ['62605', 'Staff Cost - Life Insurance',                       'CAT-13'],

            // ── 14. Post & Telecommunication ─────────────────────────────────────
            ['62203', 'Computing Expenses - WAN & Internet',               'CAT-14'],
            ['62772', 'Postage & Telephone',                               'CAT-14'],

            // ── 15. Sales Promotion & Advertising ────────────────────────────────
            ['61601', 'Advertising',                                       'CAT-15'],
            ['61801', 'Sales Promotion',                                   'CAT-15'],
            ['61813', 'Business Promotion - Brands Ambassador',            'CAT-15'],
            ['61814', 'Branded Items Events / Business Promotion',         'CAT-15'],
            ['61815', 'Bonus - Frontline',                                 'CAT-15'],
            ['62728', 'Sponsorship',                                       'CAT-15'],

            // ── 16. Other Costs ──────────────────────────────────────────────────
            ['61803', 'Subscriptions - Various Business Associations',     'CAT-16'],
            ['62704', 'CSI',                                               'CAT-16'],
            ['62710', 'Directors Fees & Expenses',                         'CAT-16'],
            ['62721', 'Donations',                                         'CAT-16'],
            ['62727', 'Business Lunch',                                    'CAT-16'],
            ['62999', 'Admin Expenses - Others',                           'CAT-16'],

            // ── 17. Depreciation of Fixed Assets ─────────────────────────────────
            ['61201', 'Depreciation - GOIL Rebranding Amortisation',       'CAT-17'],
            ['61202', 'Depreciation - Motor Vehicles',                     'CAT-17'],
            ['61203', 'Depreciation - Plant Machinery & Equipment',        'CAT-17'],
            ['62301', 'Depreciation - Leasehold Land',                     'CAT-17'],
            ['62302', 'Depreciation - Buildings',                          'CAT-17'],
            ['62303', 'Depreciation - Computers & Accessories',            'CAT-17'],
            ['62304', 'Depreciation - Furniture Fittings & Equipment',     'CAT-17'],
            ['62305', 'Depreciation - Software',                           'CAT-17'],
            ['62306', 'Depreciation - Freehold Land',                      'CAT-17'],
            ['62310', 'Depreciation - Assets Impairment',                  'CAT-17'],

            // ── 18. Financial Charges ────────────────────────────────────────────
            ['63000', 'Finance Cost',                                      'CAT-18'],
            ['63101', 'Bank Charges',                                      'CAT-18'],
            ['63102', 'Bank Interest',                                     'CAT-18'],
            ['63103', 'Exchange Losses',                                   'CAT-18'],
            ['63104', 'Interest Payable to Third Parties',                 'CAT-18'],
            ['63105', 'Floatation & Listing Expenses',                     'CAT-18'],
            ['63999', 'Finance Cost - Others',                             'CAT-18'],

            // ── 19. Sundry Charges ───────────────────────────────────────────────
            ['61808', 'Bad Debts',                                         'CAT-19'],

            // ── 20. Discount ─────────────────────────────────────────────────────
            ['61807', 'Discount Allowed',                                  'CAT-20'],

            // ── 21. Health, Safety, Security & Environment ───────────────────────
            ['62726', 'ISO Integrated Management Systems',                 'CAT-21'],
        ];

        $now  = now();
        $rows = [];

        foreach ($codes as [$code, $name, $catCode]) {
            $rows[] = [
                'account_category_id' => $cats[$catCode],
                'code'                => $code,
                'name'                => $name,
                'scope'               => 'department',
                'is_active'           => true,
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
        }

        AccountCode::upsert(
            $rows,
            ['code', 'scope'],
            ['name', 'account_category_id', 'is_active', 'updated_at']
        );
    }
}
