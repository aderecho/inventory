<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        $now = Carbon::now();

        $organizations = [
            ['short_code' => 'CAO', 'name' => 'Accounting Office', 'description' => 'Accounting Office'],
            ['short_code' => 'BAC', 'name' => 'BAC Secretariat', 'description' => 'BAC Secretariat'],
            ['short_code' => 'CBO', 'name' => 'Budget Office', 'description' => 'Budget Office'],
            ['short_code' => 'CCAD', 'name' => 'College of Communication, Art, and Design', 'description' => 'College of Communication, Art, and Design'],
            ['short_code' => 'CCAD-OCS', 'name' => 'CCAD - Office of the College Secretary', 'description' => 'CCAD - Office of the College Secretary'],
            ['short_code' => 'CMO', 'name' => 'Campus Maintenance Office', 'description' => 'Campus Maintenance Office'],
            ['short_code' => 'COS', 'name' => 'College of Science', 'description' => 'College of Science'],
            ['short_code' => 'COS-OCS', 'name' => 'College of Science - Office of the College Secretary', 'description' => 'College of Science - Office of the College Secretary'],
            ['short_code' => 'COS-OD', 'name' => 'College of Science - Office of the Dean', 'description' => 'College of Science - Office of the Dean'],
            ['short_code' => 'CS-RC', 'name' => 'College of Science - Research Center', 'description' => 'College of Science - Research Center'],
            ['short_code' => 'CSS', 'name' => 'College of Social Sciences', 'description' => 'College of Social Sciences'],
            ['short_code' => 'CSS-OCS', 'name' => 'CSS - Office of the College Secretary', 'description' => 'CSS - Office of the College Secretary'],
            ['short_code' => 'CVSC', 'name' => 'Central Visayas Studies Center', 'description' => 'Central Visayas Studies Center'],
            ['short_code' => 'CCO', 'name' => 'Cash Office', 'description' => 'Cash Office'],
            ['short_code' => 'DBES', 'name' => 'Department of Biology and Environmental Science', 'description' => 'Department of Biology and Environmental Science'],
            ['short_code' => 'DBES-PROJ', 'name' => 'DBES - Projects', 'description' => 'DBES - Projects'],
            ['short_code' => 'DCS', 'name' => 'Department of Computer Science', 'description' => 'Department of Computer Science'],
            ['short_code' => 'Dorm', 'name' => 'Dormitory', 'description' => 'Dormitory'],
            ['short_code' => 'FabLab', 'name' => 'Fablab UP Cebu', 'description' => 'Fablab UP Cebu'],
            ['short_code' => 'GAD', 'name' => 'Gender and Development', 'description' => 'Gender and Development'],
            ['short_code' => 'HRDO', 'name' => 'Human Resource and Development Office', 'description' => 'Human Resource and Development Office'],
            ['short_code' => 'LIB', 'name' => 'Library Services', 'description' => 'Library Services'],
            ['short_code' => 'HSU', 'name' => 'Health Services Unit', 'description' => 'Health Services Unit'],
            ['short_code' => 'ITC', 'name' => 'Information Technology Center', 'description' => 'Information Technology Center'],
            ['short_code' => 'LO', 'name' => 'Legal Office', 'description' => 'Legal Office'],
            ['short_code' => 'MBA', 'name' => 'Master of Business Administration', 'description' => 'Master of Business Administration'],
            ['short_code' => 'MED', 'name' => 'Master of Education', 'description' => 'Master of Education'],
            ['short_code' => 'Math', 'name' => 'Mathematics Program', 'description' => 'Mathematics Program'],
            ['short_code' => 'NSTP', 'name' => 'National Service Training Program', 'description' => 'National Service Training Program'],
            ['short_code' => 'OC', 'name' => 'Office of the Chancellor', 'description' => 'Office of the Chancellor'],
            ['short_code' => 'OC-PROJ', 'name' => 'Office of the Chancellor - Projects', 'description' => 'Office of the Chancellor - Projects'],
            ['short_code' => 'OCA', 'name' => 'Office of Campus Architect', 'description' => 'Office of Campus Architect'],
            ['short_code' => 'OCEP', 'name' => 'Office of Continuing Education & Pahinungod', 'description' => 'Office of Continuing Education & Pahinungod'],
            ['short_code' => 'OSA', 'name' => 'Office of Student Affairs', 'description' => 'Office of Student Affairs'],
            ['short_code' => 'OUR', 'name' => 'Office of the University Registrar', 'description' => 'Office of the University Registrar'],
            ['short_code' => 'OVCA', 'name' => 'Office of the Vice Chancellor for Administration', 'description' => 'Office of the Vice Chancellor for Administration'],
            ['short_code' => 'OVCAA', 'name' => 'Office of the Vice Chancellor for Academic Affairs', 'description' => 'Office of the Vice Chancellor for Academic Affairs'],
            ['short_code' => 'PIO', 'name' => 'Public Information Office', 'description' => 'Public Information Office'],
            ['short_code' => 'SOM', 'name' => 'School of Management', 'description' => 'School of Management'],
            ['short_code' => 'SOM-OCS', 'name' => 'School of Management - Office of the College Secretary', 'description' => 'School of Management - Office of the College Secretary'],
            ['short_code' => 'SPMO', 'name' => 'Supply and Property Management Office', 'description' => 'Supply and Property Management Office'],
            ['short_code' => 'SRP', 'name' => 'SRP Campus Administrator', 'description' => 'SRP Campus Administrator'],
            ['short_code' => 'SSU', 'name' => 'Safety and Security Unit', 'description' => 'Safety and Security Unit'],
            ['short_code' => 'TLRC', 'name' => 'Teaching and Learning Resource Center', 'description' => 'Teaching and Learning Resource Center'],
            ['short_code' => 'TTBDO', 'name' => 'Technology Transfer and Business Development Office', 'description' => 'Technology Transfer and Business Development Office'],
            ['short_code' => 'OASH', 'name' => 'Office of Anti Sexual Harassment', 'description' => 'Office of Anti Sexual Harassment'],
            ['short_code' => 'OICA', 'name' => 'Office for Initiatives in Culture and Arts', 'description' => 'Office for Initiatives in Culture and Arts'],
            ['short_code' => 'Pahinungod', 'name' => 'Pahinungod', 'description' => 'Pahinungod'],
            ['short_code' => 'OIL', 'name' => 'Office of International Linkages', 'description' => 'Office of International Linkages'],
            ['short_code' => 'REC', 'name' => 'Research and Ethics Committee', 'description' => 'Research and Ethics Committee'],
        ];

        $rows = array_map(function ($org) use ($now) {
            return array_merge($org, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $organizations);

        DB::table('organizations')->insert($rows);
    }

    public function down(): void
    {
        $codes = [
            'CAO', 'BAC', 'CBO', 'CCAD', 'CCAD-OCS', 'CMO', 'COS', 'COS-OCS', 'COS-OD',
            'CS-RC', 'CSS', 'CSS-OCS', 'CVSC', 'CCO', 'DBES', 'DBES-PROJ', 'DCS', 'Dorm',
            'FabLab', 'GAD', 'HRDO', 'LIB', 'HSU', 'ITC', 'LO', 'MBA', 'MED', 'Math',
            'NSTP', 'OC', 'OC-PROJ', 'OCA', 'OCEP', 'OSA', 'OUR', 'OVCA', 'OVCAA', 'PIO',
            'SOM', 'SOM-OCS', 'SPMO', 'SRP', 'SSU', 'TLRC', 'TTBDO', 'OASH', 'OICA',
            'Pahinungod', 'OIL', 'REC',
        ];

        DB::table('organizations')->whereIn('short_code', $codes)->delete();
    }
};