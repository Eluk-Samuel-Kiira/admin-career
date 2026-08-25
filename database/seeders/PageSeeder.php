<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Job\Page;
use App\Models\Job\Country;
use Illuminate\Support\Str;

class PageSeeder extends Seeder
{
    public function run()
    {
        // Get all active countries from the database
        $countries = Country::where('is_active', true)->get();

        foreach ($countries as $country) {
            $this->createPagesForCountry($country);
        }

        $this->command->info('Pages seeded successfully for all countries!');
    }

    private function createPagesForCountry($country)
    {
        $countryCode = $country->code;
        $countryName = $country->name;
        $domain = strtolower($countryCode);

        $pages = [
            [
                'slug' => 'about',
                'title' => 'About Us',
                'template' => 'default',
                'is_featured' => true,
                'content' => $this->getAboutContent($countryName, $countryCode),
                'meta_title' => "About Us - {$countryName}'s Leading AI-Powered Recruitment Platform",
                'meta_description' => "Learn about {$countryName}'s leading AI-powered recruitment platform connecting job seekers and employers with intelligent matching technology.",
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact Us',
                'template' => 'contact',
                'content' => $this->getContactContent($countryName, $countryCode),
                'meta_title' => "Contact Us - Get in Touch with {$countryName}'s Recruitment Team",
                'meta_description' => "Contact our team for support, inquiries, or partnerships. We're here to help with your recruitment or job search needs in {$countryName}.",
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'template' => 'legal',
                'content' => $this->getPrivacyContent($countryName, $countryCode),
                'meta_title' => "Privacy Policy - {$countryName} Recruitment Platform",
                'meta_description' => "Read our Privacy Policy to understand how we collect, use, and protect your personal information on our recruitment platform in {$countryName}.",
            ],
            [
                'slug' => 'terms-conditions',
                'title' => 'Terms & Conditions',
                'template' => 'legal',
                'content' => $this->getTermsContent($countryName, $countryCode),
                'meta_title' => "Terms & Conditions - {$countryName} Recruitment Platform",
                'meta_description' => "Read the Terms & Conditions for using our recruitment platform in {$countryName}. Understand your rights and responsibilities.",
            ],
        ];

        foreach ($pages as $pageData) {
            $pageData['country_code'] = $countryCode;
      
            Page::updateOrCreate(
                ['slug' => $pageData['slug'], 'country_code' => $countryCode],
                $pageData
            );
        }
    }


    private function getAboutContent($countryName, $countryCode)
    {
        return "
<h2>About Great Jobs</h2>
<p>Welcome to Great Jobs, your trusted partner in connecting talented professionals with meaningful career opportunities across {$countryName}.</p>

<h3>Our Mission</h3>
<p>Our mission is to revolutionize the job search experience by leveraging AI-powered technology to match the right talent with the right opportunities. We believe that finding a job or hiring the perfect candidate should be simple, efficient, and transparent.</p>

<h3>What We Do</h3>
<p>Great Jobs is an AI-powered recruitment platform that serves both job seekers and employers in {$countryName}. We combine cutting-edge technology with human insight to deliver:</p>
<ul>
    <li>AI-powered CV screening and ranking for employers</li>
    <li>Personalized job matching for candidates</li>
    <li>Real-time job alerts via WhatsApp and email</li>
    <li>Verified talent pools in blue-collar and casual sectors</li>
    <li>CV writing and optimization services</li>
</ul>

<h3>Our Values</h3>
<ul>
    <li><strong>Innovation:</strong> We continuously evolve our AI technology to provide the best possible matches.</li>
    <li><strong>Integrity:</strong> We are committed to transparency and fairness in all our processes.</li>
    <li><strong>Impact:</strong> We measure our success by the careers we help build and the businesses we help grow.</li>
    <li><strong>Excellence:</strong> We strive for excellence in every interaction, from user experience to customer support.</li>
</ul>

<h3>Why Choose Great Jobs?</h3>
<ul>
    <li><strong>AI-Powered Matching:</strong> Our advanced algorithms ensure the best fit between candidates and employers.</li>
    <li><strong>Verified Talent:</strong> All candidates are verified, giving employers confidence in their hires.</li>
    <li><strong>Fast & Efficient:</strong> Post jobs and find candidates in minutes, not weeks.</li>
    <li><strong>Local Expertise:</strong> We understand the {$countryName} job market and its unique dynamics.</li>
</ul>

<h3>Get in Touch</h3>
<p>Have questions? We'd love to hear from you. Reach out to our team at <a href='mailto:support@great{$countryCode}jobs.com'>support@great{$countryCode}jobs.com</a> or follow us on social media for the latest updates and job tips.</p>
";
    }

    private function getContactContent($countryName, $countryCode)
    {
        $domain = strtolower($countryCode);
        $country = Country::where('code', $countryCode)->first();
        $phone = $country->phone_code ?? '+61282947837';

        return "
<h2>Contact Us</h2>
<p>We're here to help! Whether you're a job seeker looking for the perfect role or an employer seeking top talent in {$countryName}, our team is ready to assist you.</p>

<div class='row mt-4'>
    <div class='col-md-4 mb-4'>
        <div class='contact-card'>
            <i class='ki-duotone ki-sms fs-1 text-primary d-block mb-3'><span class='path1'></span><span class='path2'></span></i>
            <h5>Email Us</h5>
            <p><a href='mailto:support@great{$domain}jobs.com'>support@great{$domain}jobs.com</a></p>
            <p>We respond within 24 hours</p>
        </div>
    </div>
    <div class='col-md-4 mb-4'>
        <div class='contact-card'>
            <i class='ki-duotone ki-phone fs-1 text-success d-block mb-3'><span class='path1'></span><span class='path2'></span></i>
            <h5>Call Us</h5>
            <p><a href='tel:{$phone}'>{$phone}</a></p>
            <p>Mon-Fri 9AM-5PM</p>
        </div>
    </div>
    <div class='col-md-4 mb-4'>
        <div class='contact-card'>
            <i class='ki-duotone ki-whatsapp fs-1 text-success d-block mb-3'><span class='path1'></span><span class='path2'></span></i>
            <h5>WhatsApp</h5>
            <p><a href='https://wa.me/" . preg_replace('/[^0-9]/', '', $phone) . "'>Chat with us</a></p>
            <p>Fastest way to get support</p>
        </div>
    </div>
</div>

<h3>Our Office</h3>
<div class='row'>
    <div class='col-md-6'>
        <p><strong>Address:</strong><br>
        {$this->getOfficeAddress($countryCode)}</p>
        <p><strong>Business Hours:</strong><br>
        Monday – Friday: 9:00 AM – 5:00 PM<br>
        Saturday – Sunday: Closed</p>
    </div>
    <div class='col-md-6'>
        <div style='background:#f0f0f0; border-radius:12px; padding:20px; min-height:150px; display:flex; align-items:center; justify-content:center;'>
            <span class='text-muted'>Map location will appear here</span>
        </div>
    </div>
</div>

<h3>Frequently Asked Questions</h3>
<p>Before contacting us, you might find answers in our <a href='/faq'>FAQ section</a>.</p>

<p class='mt-4'><strong>For media inquiries:</strong> <a href='mailto:media@great{$domain}jobs.com'>media@great{$domain}jobs.com</a></p>
";
    }

    private function getPrivacyContent($countryName, $countryCode)
    {
        $domain = strtolower($countryCode);
        $date = date('F d, Y');

        return "
<h2>Privacy Policy</h2>
<p><strong>Last Updated:</strong> {$date}</p>

<p>Welcome to Great Jobs {$countryName}. We are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, and safeguard your data when you use our platform.</p>

<h3>Information We Collect</h3>
<p>We collect information to provide better services to all our users. The types of information we collect include:</p>
<ul>
    <li><strong>Personal Identification:</strong> Name, email address, phone number, and other contact details.</li>
    <li><strong>Professional Information:</strong> CV/resume, work experience, education, skills, certifications, and job preferences.</li>
    <li><strong>Usage Data:</strong> How you interact with our platform, including searches, applications, and preferences.</li>
    <li><strong>Device Information:</strong> IP address, browser type, device identifiers, and operating system.</li>
</ul>

<h3>How We Use Your Information</h3>
<ul>
    <li><strong>Job Matching:</strong> To match your profile with relevant job opportunities in {$countryName}</li>
    <li><strong>Application Processing:</strong> To facilitate job applications and hiring processes</li>
    <li><strong>Personalization:</strong> To customize your experience and provide relevant recommendations</li>
    <li><strong>Communication:</strong> To send job alerts, updates, and important notifications</li>
    <li><strong>Analytics:</strong> To improve our platform and user experience</li>
</ul>

<h3>Information Sharing</h3>
<p>We do not sell or rent your personal information to third parties. We may share your information with:</p>
<ul>
    <li><strong>Employers:</strong> When you apply for a position, your relevant information is shared with the employer</li>
    <li><strong>Service Providers:</strong> Third-party vendors who assist in operating our platform</li>
    <li><strong>Legal Authorities:</strong> When required by law or to protect our rights</li>
</ul>

<h3>Data Security</h3>
<p>We implement robust security measures to protect your personal information from unauthorized access, disclosure, or alteration. These include:</p>
<ul>
    <li>Encryption of sensitive data</li>
    <li>Secure servers and network infrastructure</li>
    <li>Regular security audits and updates</li>
    <li>Access controls and authentication protocols</li>
</ul>

<h3>Your Rights</h3>
<p>You have the right to:</p>
<ul>
    <li>Access and review your personal information</li>
    <li>Request corrections to your data</li>
    <li>Request deletion of your account and data</li>
    <li>Opt-out of marketing communications</li>
    <li>Export your data in a portable format</li>
</ul>

<h3>Cookies</h3>
<p>We use cookies to enhance your browsing experience and analyze platform usage. You can control cookie preferences in your browser settings.</p>

<h3>Children's Privacy</h3>
<p>Our platform is not intended for individuals under 18 years of age. We do not knowingly collect personal information from minors.</p>

<h3>Changes to This Policy</h3>
<p>We may update this Privacy Policy periodically. We will notify you of any significant changes through our platform or via email.</p>

<h3>Contact Us</h3>
<p>If you have questions about this Privacy Policy, please contact us at <a href='mailto:privacy@great{$domain}jobs.com'>privacy@great{$domain}jobs.com</a>.</p>
";
    }

    private function getTermsContent($countryName, $countryCode)
    {
        $domain = strtolower($countryCode);
        $date = date('F d, Y');

        return "
<h2>Terms & Conditions</h2>
<p><strong>Last Updated:</strong> {$date}</p>

<p>Welcome to Great Jobs {$countryName}. By using our platform, you agree to comply with and be bound by the following terms and conditions. Please read them carefully.</p>

<h3>Acceptance of Terms</h3>
<p>By creating an account or using Great Jobs {$countryName}, you acknowledge that you have read, understood, and agree to be bound by these Terms & Conditions. If you do not agree, please do not use our platform.</p>

<h3>User Accounts</h3>
<ul>
    <li>You must be at least 18 years old to create an account</li>
    <li>You are responsible for maintaining the confidentiality of your account credentials</li>
    <li>You agree to provide accurate and complete information</li>
    <li>You are responsible for all activities that occur under your account</li>
</ul>

<h3>Job Seekers</h3>
<ul>
    <li>You may create a profile and apply for positions</li>
    <li>You grant us permission to share your information with employers during applications</li>
    <li>You must not submit false or misleading information</li>
    <li>You agree to receive job alerts and communications relevant to your interests</li>
</ul>

<h3>Employers</h3>
<ul>
    <li>You may post job listings and access candidate information</li>
    <li>You agree to provide accurate job descriptions and requirements</li>
    <li>You must not discriminate in your hiring practices</li>
    <li>You agree to pay any applicable fees for premium services</li>
</ul>

<h3>Prohibited Activities</h3>
<p>You agree not to:</p>
<ul>
    <li>Post fraudulent or misleading job listings</li>
    <li>Submit false or unauthorized applications</li>
    <li>Use our platform for unsolicited advertising</li>
    <li>Attempt to gain unauthorized access to our systems</li>
    <li>Harass, abuse, or harm other users</li>
    <li>Violate any applicable laws or regulations</li>
</ul>

<h3>Content Ownership</h3>
<p>Job seekers retain ownership of their profile information. Employers retain ownership of job listings. By submitting content, you grant us a license to display and use it on our platform.</p>

<h3>Disclaimer of Warranties</h3>
<p>Great Jobs {$countryName} is provided 'as is' without warranties of any kind. We do not guarantee employment outcomes or the accuracy of information on our platform.</p>

<h3>Limitation of Liability</h3>
<p>We are not liable for damages arising from:</p>
<ul>
    <li>Use or inability to use our platform</li>
    <li>Employment outcomes or decisions made based on our service</li>
    <li>Third-party conduct or content</li>
    <li>Technical interruptions or data loss</li>
</ul>

<h3>Indemnification</h3>
<p>You agree to indemnify Great Jobs {$countryName} against claims arising from your use of the platform or violation of these terms.</p>

<h3>Termination</h3>
<p>We reserve the right to suspend or terminate accounts for violations of these terms or activities that harm our platform or users.</p>

<h3>Changes to Terms</h3>
<p>We may update these terms periodically. Continued use of our platform constitutes acceptance of the updated terms.</p>

<h3>Governing Law</h3>
<p>These terms are governed by the laws of {$countryName}. Any disputes shall be resolved in the appropriate courts.</p>

<h3>Contact</h3>
<p>For questions about these terms, contact us at <a href='mailto:legal@great{$domain}jobs.com'>legal@great{$domain}jobs.com</a>.</p>
";
    }

    private function getOfficeAddress($countryCode)
    {
        $addresses = [
            'AU' => 'Level 23, 55 Hunter Street<br>Sydney, NSW 2000<br>Australia',
            'UG' => 'Plot 15, Kampala Road<br>Kampala, Uganda',
            'KE' => 'Moi Avenue, Nairobi<br>Nairobi, Kenya',
            'TZ' => 'Samora Avenue, Dar es Salaam<br>Dar es Salaam, Tanzania',
            'RW' => 'KN 5 Rd, Kigali<br>Kigali, Rwanda',
            'MW' => 'Victoria Avenue, Blantyre<br>Blantyre, Malawi',
            'ZM' => 'Cairo Road, Lusaka<br>Lusaka, Zambia',
            'SG' => '1 Raffles Place, Singapore<br>Singapore 048616',
        ];
        return $addresses[$countryCode] ?? $addresses['AU'];
    }
}