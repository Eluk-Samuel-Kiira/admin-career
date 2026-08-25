<?php
// database/seeders/ExperienceLevelSeeder.php

namespace Database\Seeders;

use App\Models\Job\ExperienceLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExperienceLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $experienceLevels = [
            // ===== NO EXPERIENCE / ENTRY LEVEL =====
            [
                'name' => 'No Experience Required',
                'slug' => 'no-experience-jobs-uganda',
                'description' => 'Jobs suitable for candidates with no prior work experience. Perfect for fresh graduates, school leavers, and those entering the workforce for the first time.',
                'min_years' => 0,
                'max_years' => 0,
                'meta_title' => 'No Experience Jobs in Uganda - Entry Level Opportunities',
                'meta_description' => 'Find jobs in Uganda with no experience required. Perfect for fresh graduates, school leavers, and first-time job seekers. Start your career today!',
                'sort_order' => 1
            ],
            [
                'name' => 'Internship',
                'slug' => 'internship-jobs-uganda',
                'description' => 'Temporary positions designed for students or recent graduates to gain practical work experience. Often leads to permanent employment.',
                'min_years' => 0,
                'max_years' => 0,
                'meta_title' => 'Internship Opportunities in Uganda - Graduate Trainee Programs',
                'meta_description' => 'Browse internship opportunities in Uganda for students and recent graduates. Gain valuable work experience and kickstart your career.',
                'sort_order' => 2
            ],
            [
                'name' => 'Graduate Trainee',
                'slug' => 'graduate-trainee-jobs-uganda',
                'description' => 'Structured training programs for recent university graduates. Combines formal training with hands-on work experience.',
                'min_years' => 0,
                'max_years' => 1,
                'meta_title' => 'Graduate Trainee Jobs in Uganda - Fresh Graduate Programs',
                'meta_description' => 'Find graduate trainee positions in Uganda. Structured training programs for recent university graduates across various industries.',
                'sort_order' => 3
            ],
            [
                'name' => 'Apprenticeship',
                'slug' => 'apprenticeship-jobs-uganda',
                'description' => 'On-the-job training programs combining practical work with learning. Common in trades, crafts, and technical fields.',
                'min_years' => 0,
                'max_years' => 0,
                'meta_title' => 'Apprenticeship Programs in Uganda - Trade & Technical Training',
                'meta_description' => 'Browse apprenticeship opportunities in Uganda. Combine on-the-job training with learning in trades, crafts, and technical fields.',
                'sort_order' => 4
            ],
            
            // ===== ENTRY TO JUNIOR LEVEL =====
            [
                'name' => 'Less than 1 Year',
                'slug' => 'less-than-1-year-experience-jobs-uganda',
                'description' => 'Entry-level positions requiring minimal experience. Suitable for candidates who have completed internships or have basic familiarity with the field.',
                'min_years' => 0,
                'max_years' => 1,
                'meta_title' => 'Entry Level Jobs in Uganda - Less than 1 Year Experience',
                'meta_description' => 'Find entry level jobs in Uganda requiring less than 1 year experience. Perfect for candidates with internship experience or basic industry familiarity.',
                'sort_order' => 5
            ],
            [
                'name' => '1 Year Experience',
                'slug' => '1-year-experience-jobs-uganda',
                'description' => 'Positions requiring one year of relevant work experience. Suitable for candidates who have completed their first year in the workforce.',
                'min_years' => 1,
                'max_years' => 1,
                'meta_title' => 'Jobs with 1 Year Experience in Uganda',
                'meta_description' => 'Browse jobs in Uganda requiring 1 year of experience. Perfect for candidates with one year of relevant work experience.',
                'sort_order' => 6
            ],
            [
                'name' => '2 Years Experience',
                'slug' => '2-years-experience-jobs-uganda',
                'description' => 'Junior-level positions requiring two years of relevant experience. Candidates should have developed basic competency in their field.',
                'min_years' => 2,
                'max_years' => 2,
                'meta_title' => 'Jobs with 2 Years Experience in Uganda - Junior Level',
                'meta_description' => 'Find jobs in Uganda requiring 2 years experience. Junior-level positions for candidates with established basic competency.',
                'sort_order' => 7
            ],
            
            // ===== MID-LEVEL =====
            [
                'name' => '3 Years Experience',
                'slug' => '3-years-experience-jobs-uganda',
                'description' => 'Mid-level positions requiring three years of experience. Candidates should have solid understanding and ability to work independently.',
                'min_years' => 3,
                'max_years' => 3,
                'meta_title' => 'Jobs with 3 Years Experience in Uganda - Mid Level',
                'meta_description' => 'Browse mid-level jobs in Uganda requiring 3 years experience. Positions for candidates with solid understanding and independent work capability.',
                'sort_order' => 8
            ],
            [
                'name' => '4 Years Experience',
                'slug' => '4-years-experience-jobs-uganda',
                'description' => 'Mid-level positions requiring four years of experience. Candidates should have proven track record and ability to handle complex tasks.',
                'min_years' => 4,
                'max_years' => 4,
                'meta_title' => 'Jobs with 4 Years Experience in Uganda',
                'meta_description' => 'Find jobs in Uganda requiring 4 years experience. Mid-level positions for candidates with proven track record.',
                'sort_order' => 9
            ],
            [
                'name' => '5 Years Experience',
                'slug' => '5-years-experience-jobs-uganda',
                'description' => 'Established mid-level positions requiring five years of experience. Candidates should have specialized knowledge and may supervise junior staff.',
                'min_years' => 5,
                'max_years' => 5,
                'meta_title' => 'Jobs with 5 Years Experience in Uganda - Experienced Professionals',
                'meta_description' => 'Browse jobs in Uganda requiring 5 years experience. Established mid-level positions for experienced professionals with specialized knowledge.',
                'sort_order' => 10
            ],
            
            // ===== SENIOR LEVEL =====
            [
                'name' => '6 Years Experience',
                'slug' => '6-years-experience-jobs-uganda',
                'description' => 'Senior-level positions requiring six years of experience. Candidates should have leadership abilities and strategic thinking skills.',
                'min_years' => 6,
                'max_years' => 6,
                'meta_title' => 'Jobs with 6 Years Experience in Uganda - Senior Level',
                'meta_description' => 'Find senior-level jobs in Uganda requiring 6 years experience. Positions for candidates with leadership abilities and strategic thinking.',
                'sort_order' => 11
            ],
            [
                'name' => '7 Years Experience',
                'slug' => '7-years-experience-jobs-uganda',
                'description' => 'Senior-level positions requiring seven years of experience. Candidates should have management experience and industry expertise.',
                'min_years' => 7,
                'max_years' => 7,
                'meta_title' => 'Jobs with 7 Years Experience in Uganda',
                'meta_description' => 'Browse senior-level jobs in Uganda requiring 7 years experience. Positions for candidates with management experience and industry expertise.',
                'sort_order' => 12
            ],
            [
                'name' => '8 Years Experience',
                'slug' => '8-years-experience-jobs-uganda',
                'description' => 'Senior-level positions requiring eight years of experience. Candidates should have proven leadership and strategic decision-making abilities.',
                'min_years' => 8,
                'max_years' => 8,
                'meta_title' => 'Jobs with 8 Years Experience in Uganda',
                'meta_description' => 'Find jobs in Uganda requiring 8 years experience. Senior positions for candidates with proven leadership and strategic abilities.',
                'sort_order' => 13
            ],
            [
                'name' => '9 Years Experience',
                'slug' => '9-years-experience-jobs-uganda',
                'description' => 'Senior-level positions requiring nine years of experience. Candidates should have extensive industry knowledge and team leadership experience.',
                'min_years' => 9,
                'max_years' => 9,
                'meta_title' => 'Jobs with 9 Years Experience in Uganda',
                'meta_description' => 'Browse jobs in Uganda requiring 9 years experience. Senior positions for candidates with extensive industry knowledge.',
                'sort_order' => 14
            ],
            [
                'name' => '10 Years Experience',
                'slug' => '10-years-experience-jobs-uganda',
                'description' => 'Senior to executive-level positions requiring ten years of experience. Candidates should have significant achievements and industry recognition.',
                'min_years' => 10,
                'max_years' => 10,
                'meta_title' => 'Jobs with 10 Years Experience in Uganda - Senior Executive',
                'meta_description' => 'Find senior to executive-level jobs in Uganda requiring 10 years experience. Positions for candidates with significant achievements and industry recognition.',
                'sort_order' => 15
            ],
            
            // ===== EXECUTIVE LEVEL =====
            [
                'name' => '11-12 Years Experience',
                'slug' => '11-12-years-experience-jobs-uganda',
                'description' => 'Executive-level positions requiring over a decade of experience. Candidates should have strategic leadership experience and industry influence.',
                'min_years' => 11,
                'max_years' => 12,
                'meta_title' => 'Executive Jobs with 11-12 Years Experience in Uganda',
                'meta_description' => 'Browse executive-level jobs in Uganda requiring 11-12 years experience. Positions for candidates with strategic leadership and industry influence.',
                'sort_order' => 16
            ],
            [
                'name' => '13-15 Years Experience',
                'slug' => '13-15-years-experience-jobs-uganda',
                'description' => 'Senior executive positions requiring extensive experience. Candidates should have C-level or senior management experience.',
                'min_years' => 13,
                'max_years' => 15,
                'meta_title' => 'Senior Executive Jobs with 13-15 Years Experience in Uganda',
                'meta_description' => 'Find senior executive jobs in Uganda requiring 13-15 years experience. C-level and senior management positions.',
                'sort_order' => 17
            ],
            [
                'name' => '15+ Years Experience',
                'slug' => '15-plus-years-experience-jobs-uganda',
                'description' => 'Top executive and advisory positions requiring extensive leadership experience. Candidates should have board-level or C-suite experience.',
                'min_years' => 15,
                'max_years' => null,
                'meta_title' => 'Top Executive Jobs with 15+ Years Experience in Uganda',
                'meta_description' => 'Browse top executive and advisory positions in Uganda requiring 15+ years experience. Board-level and C-suite opportunities.',
                'sort_order' => 18
            ],
            [
                'name' => '20+ Years Experience',
                'slug' => '20-plus-years-experience-jobs-uganda',
                'description' => 'Very senior advisory, board, and special advisor positions requiring extensive industry leadership.',
                'min_years' => 20,
                'max_years' => null,
                'meta_title' => 'Senior Advisory Jobs with 20+ Years Experience in Uganda',
                'meta_description' => 'Find very senior advisory and board positions in Uganda requiring 20+ years experience.',
                'sort_order' => 19
            ],
            
            // ===== SPECIALIZED CATEGORIES =====
            [
                'name' => 'Fresh Graduate',
                'slug' => 'fresh-graduate-jobs-uganda',
                'description' => 'Positions specifically designed for candidates who have recently completed their university education.',
                'min_years' => 0,
                'max_years' => 0,
                'meta_title' => 'Fresh Graduate Jobs in Uganda - Recent Graduates',
                'meta_description' => 'Browse jobs in Uganda specifically for fresh graduates. Start your career with opportunities designed for recent university graduates.',
                'sort_order' => 20
            ],
            [
                'name' => 'Entry Level',
                'slug' => 'entry-level-jobs-uganda',
                'description' => 'Entry-level positions across all industries. Suitable for candidates with minimal to no experience.',
                'min_years' => 0,
                'max_years' => 2,
                'meta_title' => 'Entry Level Jobs in Uganda - Start Your Career',
                'meta_description' => 'Find entry level jobs in Uganda across all industries. Perfect for candidates starting their career journey.',
                'sort_order' => 21
            ],
            [
                'name' => 'Mid-Level',
                'slug' => 'mid-level-jobs-uganda',
                'description' => 'Positions for experienced professionals with established careers. Candidates work independently and may supervise others.',
                'min_years' => 3,
                'max_years' => 6,
                'meta_title' => 'Mid-Level Jobs in Uganda - Experienced Professionals',
                'meta_description' => 'Browse mid-level jobs in Uganda for experienced professionals. Positions requiring independent work and supervisory skills.',
                'sort_order' => 22
            ],
            [
                'name' => 'Senior Level',
                'slug' => 'senior-level-jobs-uganda',
                'description' => 'Senior positions requiring significant experience and leadership abilities. Candidates manage teams and make strategic decisions.',
                'min_years' => 7,
                'max_years' => 12,
                'meta_title' => 'Senior Level Jobs in Uganda - Leadership Positions',
                'meta_description' => 'Find senior level jobs in Uganda requiring leadership experience. Positions for managers, heads of departments, and senior specialists.',
                'sort_order' => 23
            ],
            [
                'name' => 'Management',
                'slug' => 'management-jobs-uganda',
                'description' => 'Management positions requiring team leadership and people management experience.',
                'min_years' => 5,
                'max_years' => 10,
                'meta_title' => 'Management Jobs in Uganda - Team Leadership',
                'meta_description' => 'Browse management jobs in Uganda. Positions requiring team leadership, people management, and supervisory experience.',
                'sort_order' => 24
            ],
            [
                'name' => 'Senior Management',
                'slug' => 'senior-management-jobs-uganda',
                'description' => 'Senior management positions requiring extensive leadership experience and strategic oversight.',
                'min_years' => 10,
                'max_years' => 15,
                'meta_title' => 'Senior Management Jobs in Uganda - Strategic Leadership',
                'meta_description' => 'Find senior management jobs in Uganda. Positions requiring strategic oversight, department leadership, and extensive experience.',
                'sort_order' => 25
            ],
            [
                'name' => 'Executive / C-Level',
                'slug' => 'executive-c-level-jobs-uganda',
                'description' => 'C-level executive positions including CEO, CFO, CTO, COO, and other top leadership roles.',
                'min_years' => 12,
                'max_years' => null,
                'meta_title' => 'Executive & C-Level Jobs in Uganda - Top Leadership',
                'meta_description' => 'Browse executive and C-level jobs in Uganda. CEO, CFO, CTO, COO, and other top leadership positions.',
                'sort_order' => 26
            ],
            [
                'name' => 'Director Level',
                'slug' => 'director-level-jobs-uganda',
                'description' => 'Director-level positions across various departments and functions.',
                'min_years' => 10,
                'max_years' => 15,
                'meta_title' => 'Director Level Jobs in Uganda - Department Leadership',
                'meta_description' => 'Find director level jobs in Uganda. Positions requiring departmental leadership and strategic management.',
                'sort_order' => 27
            ],
            [
                'name' => 'Board Level',
                'slug' => 'board-level-jobs-uganda',
                'description' => 'Board of directors positions including non-executive directors, chairpersons, and board advisors.',
                'min_years' => 15,
                'max_years' => null,
                'meta_title' => 'Board Level Positions in Uganda - Corporate Governance',
                'meta_description' => 'Browse board level positions in Uganda including non-executive directors, chairpersons, and board advisors.',
                'sort_order' => 28
            ],
            
            // ===== CONSULTANCY & ADVISORY =====
            [
                'name' => 'Consultant Level',
                'slug' => 'consultant-level-jobs-uganda',
                'description' => 'Consultancy positions requiring specialized expertise and advisory skills.',
                'min_years' => 8,
                'max_years' => null,
                'meta_title' => 'Consultant Jobs in Uganda - Advisory & Specialized Roles',
                'meta_description' => 'Find consultant level jobs in Uganda. Positions requiring specialized expertise, advisory skills, and professional consulting experience.',
                'sort_order' => 29
            ],
            [
                'name' => 'Senior Consultant',
                'slug' => 'senior-consultant-jobs-uganda',
                'description' => 'Senior consultancy positions with extensive industry experience and client advisory responsibilities.',
                'min_years' => 10,
                'max_years' => null,
                'meta_title' => 'Senior Consultant Jobs in Uganda',
                'meta_description' => 'Browse senior consultant jobs in Uganda requiring extensive industry experience and client advisory skills.',
                'sort_order' => 30
            ],
            [
                'name' => 'Principal Consultant',
                'slug' => 'principal-consultant-jobs-uganda',
                'description' => 'Principal-level consultancy positions with practice leadership and business development responsibilities.',
                'min_years' => 12,
                'max_years' => null,
                'meta_title' => 'Principal Consultant Jobs in Uganda',
                'meta_description' => 'Find principal consultant jobs in Uganda requiring practice leadership and business development experience.',
                'sort_order' => 31
            ],
            
            // ===== ACADEMIC & RESEARCH =====
            [
                'name' => 'Teaching Assistant',
                'slug' => 'teaching-assistant-jobs-uganda',
                'description' => 'Academic support positions for graduate students or early-career academics.',
                'min_years' => 0,
                'max_years' => 2,
                'meta_title' => 'Teaching Assistant Jobs in Uganda - Academic Support',
                'meta_description' => 'Browse teaching assistant positions in Uganda for graduate students and early-career academics.',
                'sort_order' => 32
            ],
            [
                'name' => 'Lecturer',
                'slug' => 'lecturer-jobs-uganda',
                'description' => 'University and tertiary institution teaching positions.',
                'min_years' => 3,
                'max_years' => 5,
                'meta_title' => 'Lecturer Jobs in Uganda - University Teaching Positions',
                'meta_description' => 'Find lecturer jobs in Uganda at universities and tertiary institutions.',
                'sort_order' => 33
            ],
            [
                'name' => 'Senior Lecturer',
                'slug' => 'senior-lecturer-jobs-uganda',
                'description' => 'Senior academic positions with research supervision and curriculum development responsibilities.',
                'min_years' => 6,
                'max_years' => 10,
                'meta_title' => 'Senior Lecturer Jobs in Uganda',
                'meta_description' => 'Browse senior lecturer positions in Uganda requiring research supervision and academic leadership.',
                'sort_order' => 34
            ],
            [
                'name' => 'Associate Professor',
                'slug' => 'associate-professor-jobs-uganda',
                'description' => 'Mid-level professorial positions with significant research output and academic leadership.',
                'min_years' => 8,
                'max_years' => 12,
                'meta_title' => 'Associate Professor Jobs in Uganda',
                'meta_description' => 'Find associate professor jobs in Uganda requiring significant research output and academic leadership.',
                'sort_order' => 35
            ],
            [
                'name' => 'Professor',
                'slug' => 'professor-jobs-uganda',
                'description' => 'Full professor positions requiring distinguished academic career and research leadership.',
                'min_years' => 12,
                'max_years' => null,
                'meta_title' => 'Professor Jobs in Uganda - Distinguished Academic Positions',
                'meta_description' => 'Browse professor positions in Uganda requiring distinguished academic career and research leadership.',
                'sort_order' => 36
            ],
            [
                'name' => 'Research Assistant',
                'slug' => 'research-assistant-jobs-uganda',
                'description' => 'Entry-level research positions supporting ongoing research projects.',
                'min_years' => 0,
                'max_years' => 2,
                'meta_title' => 'Research Assistant Jobs in Uganda',
                'meta_description' => 'Find research assistant positions in Uganda supporting academic and institutional research.',
                'sort_order' => 37
            ],
            [
                'name' => 'Research Fellow',
                'slug' => 'research-fellow-jobs-uganda',
                'description' => 'Mid-level research positions with independent research responsibilities.',
                'min_years' => 3,
                'max_years' => 6,
                'meta_title' => 'Research Fellow Jobs in Uganda',
                'meta_description' => 'Browse research fellow positions in Uganda requiring independent research capabilities.',
                'sort_order' => 38
            ],
            [
                'name' => 'Senior Research Fellow',
                'slug' => 'senior-research-fellow-jobs-uganda',
                'description' => 'Senior research positions leading research teams and securing research funding.',
                'min_years' => 7,
                'max_years' => 12,
                'meta_title' => 'Senior Research Fellow Jobs in Uganda',
                'meta_description' => 'Find senior research fellow jobs in Uganda leading research teams and securing funding.',
                'sort_order' => 39
            ],
            
            // ===== VOLUNTEER & COMMUNITY =====
            [
                'name' => 'Volunteer',
                'slug' => 'volunteer-jobs-uganda',
                'description' => 'Unpaid volunteer positions with NGOs, community organizations, and development programs.',
                'min_years' => 0,
                'max_years' => null,
                'meta_title' => 'Volunteer Opportunities in Uganda - Community Service',
                'meta_description' => 'Browse volunteer opportunities in Uganda with NGOs and community organizations. Give back while gaining experience.',
                'sort_order' => 40
            ],
            [
                'name' => 'Community Service',
                'slug' => 'community-service-jobs-uganda',
                'description' => 'Community-focused positions with local organizations and development initiatives.',
                'min_years' => 0,
                'max_years' => null,
                'meta_title' => 'Community Service Jobs in Uganda',
                'meta_description' => 'Find community service positions in Uganda with local organizations and development initiatives.',
                'sort_order' => 41
            ],
            
            // ===== FELLOWSHIP & SCHOLARSHIP =====
            [
                'name' => 'Fellowship',
                'slug' => 'fellowship-jobs-uganda',
                'description' => 'Structured professional development programs for early to mid-career professionals.',
                'min_years' => 2,
                'max_years' => 8,
                'meta_title' => 'Fellowship Programs in Uganda - Professional Development',
                'meta_description' => 'Browse fellowship opportunities in Uganda for professional development and career advancement.',
                'sort_order' => 42
            ],
            [
                'name' => 'Post-Doctoral Fellowship',
                'slug' => 'post-doctoral-fellowship-jobs-uganda',
                'description' => 'Advanced research fellowships for recent PhD graduates.',
                'min_years' => 0,
                'max_years' => 3,
                'meta_title' => 'Post-Doctoral Fellowships in Uganda',
                'meta_description' => 'Find post-doctoral fellowship opportunities in Uganda for recent PhD graduates.',
                'sort_order' => 43
            ],
            
            // ===== SPECIALIZED =====
            [
                'name' => 'Subject Matter Expert',
                'slug' => 'subject-matter-expert-jobs-uganda',
                'description' => 'Expert-level positions requiring deep specialized knowledge in specific domains.',
                'min_years' => 8,
                'max_years' => null,
                'meta_title' => 'Subject Matter Expert Jobs in Uganda',
                'meta_description' => 'Find subject matter expert positions in Uganda requiring deep specialized knowledge.',
                'sort_order' => 44
            ],
            [
                'name' => 'Technical Advisor',
                'slug' => 'technical-advisor-jobs-uganda',
                'description' => 'Advisory positions providing technical guidance and expertise.',
                'min_years' => 8,
                'max_years' => null,
                'meta_title' => 'Technical Advisor Jobs in Uganda',
                'meta_description' => 'Browse technical advisor positions in Uganda providing specialized guidance and expertise.',
                'sort_order' => 45
            ],
            [
                'name' => 'Policy Advisor',
                'slug' => 'policy-advisor-jobs-uganda',
                'description' => 'Policy-focused advisory positions in government, NGOs, and international organizations.',
                'min_years' => 7,
                'max_years' => null,
                'meta_title' => 'Policy Advisor Jobs in Uganda',
                'meta_description' => 'Find policy advisor jobs in Uganda with government, NGOs, and international organizations.',
                'sort_order' => 46
            ],
            
            // ===== BROAD CATEGORIES =====
            [
                'name' => 'Any Experience Level',
                'slug' => 'any-experience-level-jobs-uganda',
                'description' => 'Positions open to candidates at any career stage. Employers will consider all experience levels.',
                'min_years' => null,
                'max_years' => null,
                'meta_title' => 'Jobs for All Experience Levels in Uganda',
                'meta_description' => 'Browse jobs in Uganda open to candidates at any experience level. From entry-level to executive positions.',
                'sort_order' => 47
            ],
            [
                'name' => 'Not Specified',
                'slug' => 'experience-not-specified-jobs-uganda',
                'description' => 'Positions where experience requirements are not specified. Candidates encouraged to apply based on skills and qualifications.',
                'min_years' => null,
                'max_years' => null,
                'meta_title' => 'Jobs with Unspecified Experience in Uganda',
                'meta_description' => 'Find jobs in Uganda with unspecified experience requirements. Apply based on your skills and qualifications.',
                'sort_order' => 48
            ],
        ];

        foreach ($experienceLevels as $level) {
            ExperienceLevel::firstOrCreate(
                ['slug' => $level['slug']],
                $level
            );
        }

        $this->command->info(count($experienceLevels) . ' experience levels seeded successfully!');
    }
}