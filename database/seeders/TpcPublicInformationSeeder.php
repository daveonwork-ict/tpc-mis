<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TpcPublicInformationSeeder extends Seeder
{
    private const EMAIL = 'tarlacprovincialcollege@gmail.com';

    public function run(): void
    {
        DB::transaction(function () {
            $this->renameExistingSlugs();

            foreach ($this->pages() as $page) {
                $this->upsertPage($page);
            }

            foreach ($this->notices() as $notice) {
                $this->upsertNotice($notice);
            }
        });
    }

    private function renameExistingSlugs(): void
    {
        $slugs = [
            'privacy_policy'   => 'privacy-policy',
            'support_policy'   => 'support',
            'terms_conditions' => 'terms-of-admission',
            'our_missions'     => 'purposes-of-the-college',
        ];

        foreach ($slugs as $old => $new) {
            DB::table('pages')->where('slug', $old)->update(['slug' => $new]);
        }
    }

    private function upsertPage(array $page): void
    {
        $now = now();
        $existing = DB::table('pages')->where('slug', $page['slug'])->first();

        $values = [
            'name'          => $page['name'],
            'content'       => $page['content'],
            'menu_show'     => $page['menu_show'],
            'active_status' => 1,
            'updated_at'    => $now,
        ];

        if ($existing) {
            DB::table('pages')->where('id', $existing->id)->update($values);
            $id = $existing->id;
        } else {
            $id = DB::table('pages')->insertGetId($values + [
                'slug'       => $page['slug'],
                'created_at' => $now,
                'branch_id'  => 1,
            ]);
        }

        foreach (['en', 'bn'] as $locale) {
            DB::table('page_translates')->updateOrInsert(
                ['page_id' => $id, 'locale' => $locale],
                ['name' => $page['name'], 'content' => $page['content'], 'updated_at' => $now, 'created_at' => $now, 'branch_id' => 1]
            );
        }
    }

    private function upsertNotice(array $notice): void
    {
        $now = now();
        $existing = DB::table('notice_boards')->where('title', $notice['title'])->first();

        $values = [
            'title'          => $notice['title'],
            'session_id'     => (int) (setting('session') ?: 1),
            'date'           => $notice['date'],
            'publish_date'   => $notice['date'] . ' 08:00:00',
            'description'    => $notice['description'],
            'is_visible_web' => 1,
            'status'         => 1,
            'updated_at'     => $now,
        ];

        if ($existing) {
            DB::table('notice_boards')->where('id', $existing->id)->update($values);
            $id = $existing->id;
        } else {
            $id = DB::table('notice_boards')->insertGetId($values + ['created_at' => $now, 'branch_id' => 1]);
        }

        foreach (['en', 'bn'] as $locale) {
            DB::table('notice_board_translates')->updateOrInsert(
                ['notice_board_id' => $id, 'locale' => $locale],
                ['title' => $notice['title'], 'description' => $notice['description'], 'updated_at' => $now, 'created_at' => $now, 'branch_id' => 1]
            );
        }
    }

    private function pages(): array
    {
        $email = self::EMAIL;
        $mailto = '<a href="mailto:' . $email . '">' . $email . '</a>';

        return [
            [
                'slug'      => 'privacy-policy',
                'name'      => 'Privacy Policy',
                'menu_show' => 'policy',
                'content'   => <<<HTML
<p class="tpc-doc__lead">Tarlac Provincial College (TPC), a department of the Provincial Government of Tarlac, respects your privacy and protects the personal data entrusted to it. This Privacy Policy explains how the College collects, uses, shares, keeps, and protects personal data in accordance with Republic Act No. 10173, the Data Privacy Act of 2012, its Implementing Rules and Regulations, and the issuances of the National Privacy Commission (NPC).</p>

<h3>1. Scope</h3>
<p>This policy covers personal data processed through this website, the online admission system, the student and guardian portals, and the offices of the College, including the main campus at the IT Training Center, IT Park 1, Tibag, Tarlac City, and the Tarlac Center for Learning and Skills Success (TCLASS).</p>
<p>It applies to applicants, students, parents and guardians, faculty and staff, training participants, and members of the public who visit the website or contact the College.</p>

<h3>2. Personal Data We Collect</h3>
<ul>
<li><strong>Applicants and students:</strong> name, date and place of birth, sex, address, contact details, photographs, previous school records, documents submitted for admission, and enrolment and grade records.</li>
<li><strong>Parents and guardians:</strong> name, relationship to the student, occupation, and contact details.</li>
<li><strong>Scholarship applicants:</strong> information needed to assess eligibility, which may include household income and proof of disability or of membership in an indigenous cultural community. This is treated as sensitive personal information.</li>
<li><strong>Training participants:</strong> information required for TESDA registration, training, and assessment.</li>
<li><strong>Website visitors and inquirers:</strong> the name, email address, phone number, and message you send through the contact form or by email, and technical data such as IP address, browser type, and pages visited, which are recorded in server logs.</li>
</ul>

<h3>3. How We Use Personal Data</h3>
<ul>
<li>to process applications for admission, enrolment, scholarships, and training;</li>
<li>to deliver instruction, keep academic records, and issue grades, certificates, and credentials;</li>
<li>to administer student services, scholarships, and financial assistance;</li>
<li>to communicate with students, parents and guardians, and the public, including replies to inquiries;</li>
<li>to meet reporting requirements of the Provincial Government of Tarlac, the Commission on Higher Education (CHED), the Technical Education and Skills Development Authority (TESDA), the Commission on Audit (COA), and other government agencies;</li>
<li>to keep the website and the College's information systems secure and working properly.</li>
</ul>
<p>The College does not sell personal data and does not use it for purposes unrelated to those listed above.</p>

<h3>4. Legal Basis for Processing</h3>
<p>The College processes personal data on the grounds allowed by Sections 12 and 13 of the Data Privacy Act: the performance of its functions as a public educational institution under Provincial Ordinance No. 001-2026; compliance with legal obligations; the relationship between the College and its applicants, students, and personnel; and, where required, your consent. Where processing is based on consent, you may withdraw it at any time. Withdrawal does not affect processing carried out before it.</p>

<h3>5. Sharing and Disclosure</h3>
<p>Personal data is shared only when necessary and allowed by law, with:</p>
<ul>
<li>offices of the Provincial Government of Tarlac that support the College, including the Provincial Treasurer, who is the ex-officio Treasurer of the College under Section 77 of its charter;</li>
<li>CHED, TESDA, COA, and other government agencies, as required by law or regulation;</li>
<li>scholarship and financial assistance providers, for applications you have made;</li>
<li>service providers that host or maintain the College's systems, under agreements that require them to protect the data.</li>
</ul>
<p>The College may also disclose personal data when required by a court order, subpoena, or other lawful process.</p>

<h3>6. Retention and Disposal</h3>
<p>Personal data is kept only as long as needed for the purposes above or as required by law. Academic records are kept according to CHED and TESDA requirements and the records disposition schedules of the National Archives of the Philippines under Republic Act No. 9470. Records that are no longer needed are disposed of securely so that they cannot be read or rebuilt.</p>

<h3>7. Security</h3>
<p>The College applies organizational, physical, and technical measures to protect personal data against loss, misuse, unauthorized access, alteration, and disclosure. These include access limited to authorized personnel, password-protected accounts, and confidentiality obligations for personnel who handle personal data. Personal data breaches are managed and reported to the NPC and to affected individuals as required by NPC rules.</p>

<h3>8. Your Rights</h3>
<p>Under the Data Privacy Act, you have the right to:</p>
<ul>
<li><strong>be informed</strong> whether and how your personal data is processed;</li>
<li><strong>access</strong> the personal data the College holds about you;</li>
<li><strong>object</strong> to processing, including processing for direct marketing or automated decisions;</li>
<li><strong>rectification</strong> of data that is inaccurate or incomplete;</li>
<li><strong>erasure or blocking</strong> of data that is unlawfully obtained, no longer necessary, or processed without legal basis;</li>
<li><strong>data portability</strong>, or a copy of your data in a commonly used electronic format;</li>
<li><strong>damages</strong> for inaccurate, incomplete, outdated, false, unlawfully obtained, or unauthorized use of your personal data; and</li>
<li><strong>file a complaint</strong> with the National Privacy Commission.</li>
</ul>
<p>Some rights may be limited where processing is required by law or is necessary for the College's public functions, such as keeping permanent academic records.</p>

<h3>9. Cookies and Embedded Content</h3>
<p>This website uses only the cookies it needs to work: a session cookie that keeps you signed in to the portals and a security token that protects forms from forgery. They are not used for advertising or tracking.</p>
<p>The Contact page shows an embedded Google Map, and some pages link to the College's official Facebook page. When you use them, Google and Meta may collect data under their own privacy policies.</p>

<h3>10. Minors</h3>
<p>When an applicant or student is below eighteen (18) years of age, the College involves a parent or guardian in the collection of personal data and asks for their consent where required.</p>

<h3>11. Contact the Data Protection Officer</h3>
<p>For questions, requests to exercise your rights, or concerns about this policy, write to:</p>
<p><strong>Data Protection Officer</strong><br>Tarlac Provincial College<br>IT Training Center, IT Park 1, Tibag, Tarlac City, Tarlac<br>Email: {$mailto} (subject: "Data Privacy")</p>
<p>If you are not satisfied with the College's response, you may file a complaint with the National Privacy Commission through <a href="https://privacy.gov.ph/" target="_blank" rel="noopener">privacy.gov.ph</a> or at <a href="mailto:complaints@privacy.gov.ph">complaints@privacy.gov.ph</a>.</p>

<h3>12. Changes to This Policy</h3>
<p>The College may update this policy when laws or its practices change. The date at the top of this page shows when it was last updated. Significant changes are also announced under <a href="/notices">Notices</a>.</p>
HTML,
            ],
            [
                'slug'      => 'terms-of-use',
                'name'      => 'Terms of Use',
                'menu_show' => 'policy',
                'content'   => <<<HTML
<p class="tpc-doc__lead">This is the official website of Tarlac Provincial College (TPC), a public technical-vocational and higher education institution established by the Provincial Government of Tarlac under Provincial Ordinance No. 001-2026. By using this website, you agree to these Terms of Use.</p>

<h3>1. Purpose of the Website</h3>
<p>The website provides public information about the College, its programs, news, events, and notices, and access to online services such as online admission and the student and guardian portals.</p>

<h3>2. Use of Content</h3>
<p>Under Section 176 of Republic Act No. 8293, the Intellectual Property Code of the Philippines, no copyright subsists in works of the Government of the Philippines. You may reproduce text published on this website if you credit Tarlac Provincial College as the source and do not alter it or use it in a misleading way. Prior approval of the College is required to use its works for profit. Photographs, third-party materials, and documents marked otherwise may be subject to the rights of their owners.</p>

<h3>3. Official Seals</h3>
<p>The seal of Tarlac Provincial College and the official seal of the Province of Tarlac identify the College and the Provincial Government. They may not be used on any product, publication, or website, or in a way that suggests official endorsement, without written permission.</p>

<h3>4. Accuracy of Information</h3>
<p>The College makes reasonable efforts to keep this website accurate and current. Signed or published ordinances, resolutions, and issuances prevail over any content on this website. Admission requirements, schedules, and fees are subject to the approval of the Governing Board and may change, so please confirm with the College before acting on them.</p>

<h3>5. Online Services and Accounts</h3>
<p>Users of the online admission system and the portals must give truthful and complete information, keep their login details confidential, and use the services only for their intended purpose. False information or documents may lead to the denial or cancellation of admission and to liability under applicable laws.</p>

<h3>6. Prohibited Use</h3>
<p>You may not:</p>
<ul>
<li>attempt to gain unauthorized access to the website, its servers, or user accounts;</li>
<li>upload malicious code or interfere with the operation of the website;</li>
<li>use automated tools to collect personal data from the website;</li>
<li>send unlawful, defamatory, or abusive content through the website's forms.</li>
</ul>
<p>These acts may be punishable under Republic Act No. 10175, the Cybercrime Prevention Act of 2012, and other laws.</p>

<h3>7. Links to Other Websites</h3>
<p>Links to government agencies and other websites are provided for convenience. The College is not responsible for their content or privacy practices.</p>

<h3>8. Availability and Liability</h3>
<p>The website is provided "as is". It may be unavailable at times for maintenance or because of technical problems. The College is not liable for losses arising from the use of, or the inability to use, the website.</p>

<h3>9. Privacy</h3>
<p>Personal data submitted through this website is handled under the College's <a href="/page/privacy-policy">Privacy Policy</a>.</p>

<h3>10. Governing Law</h3>
<p>These Terms are governed by the laws of the Republic of the Philippines.</p>

<h3>11. Changes</h3>
<p>The College may revise these Terms at any time. The date at the top of this page shows when they were last updated. Continued use of the website after changes are posted means you accept the revised Terms.</p>
HTML,
            ],
            [
                'slug'      => 'accessibility-statement',
                'name'      => 'Accessibility Statement',
                'menu_show' => 'policy',
                'content'   => <<<HTML
<p class="tpc-doc__lead">Tarlac Provincial College wants this website to be usable by everyone, including persons with disabilities, older users, and people using mobile phones or slow connections.</p>

<h3>Our Commitment</h3>
<p>This commitment is in line with Republic Act No. 7277, the Magna Carta for Persons with Disability, as amended, and with the College's charter, which gives scholarship preference to persons with disabilities. The College aims to meet the Web Content Accessibility Guidelines (WCAG) 2.1, Level AA, published by the World Wide Web Consortium (W3C).</p>

<h3>Accessibility Features</h3>
<p>Features in place or being improved include:</p>
<ul>
<li>a responsive layout that adapts to phones, tablets, and desktop screens;</li>
<li>text and background colours chosen for readable contrast;</li>
<li>text descriptions for the official seals and key images;</li>
<li>headings and page structure that work with screen readers;</li>
<li>pages that can be enlarged with the browser's zoom without losing content;</li>
<li>links and forms that can be used with a keyboard.</li>
</ul>

<h3>Known Limitations</h3>
<ul>
<li>Some documents, such as the scanned copy of the College charter, may not be fully readable by screen readers.</li>
<li>Some photographs of College activities may not yet have complete descriptions.</li>
<li>Embedded services, such as Google Maps, follow the accessibility of their providers.</li>
</ul>

<h3>Feedback and Assistance</h3>
<p>If you have difficulty using any part of this website, or need information in another format, email {$mailto} with the subject "Accessibility". Tell us the page and the problem, and the College will try to provide the information in a form you can use.</p>
HTML,
            ],
            [
                'slug'      => 'freedom-of-information',
                'name'      => 'Freedom of Information',
                'menu_show' => 'policy',
                'content'   => <<<HTML
<p class="tpc-doc__lead">Tarlac Provincial College recognizes the right of the people to information on matters of public concern, guaranteed by Article III, Section 7 of the 1987 Constitution, and the State policy of full public disclosure under Article II, Section 28.</p>

<h3>Policy</h3>
<p>As a public institution of the Province of Tarlac, the College is committed to transparency and accountability, which its charter lists among its objectives in Section 5. It follows the spirit of Executive Order No. 2, series of 2016, which put into effect the people's right to information in the Executive Branch, and the Full Disclosure Policy for local government units of the Department of the Interior and Local Government.</p>

<h3>What You Can Request</h3>
<p>You may request information, official records, and documents held by the College on its official acts, transactions, and decisions, subject to the exceptions below.</p>

<h3>Exceptions</h3>
<p>A request may be denied when the information falls under exceptions recognized by the Constitution, laws, or jurisprudence, including:</p>
<ul>
<li>personal information of students, applicants, employees, and other individuals, which is protected by the Data Privacy Act of 2012;</li>
<li>academic records, which are released only to the student concerned or to persons authorized by the student or by law;</li>
<li>privileged information, such as deliberations before a decision is made, or matters under investigation;</li>
<li>information whose disclosure is prohibited by law.</li>
</ul>

<h3>How to Make a Request</h3>
<ol>
<li>Write a request addressed to the President of Tarlac Provincial College. Include your full name, address, and contact details, a clear description of the information you need, and the purpose of your request.</li>
<li>Attach a copy of a valid government-issued ID with your photograph.</li>
<li>Email the request to {$mailto} with the subject "FOI Request", or deliver it to the College at the IT Training Center, IT Park 1, Tibag, Tarlac City.</li>
</ol>

<h3>Processing</h3>
<p>The College will acknowledge your request and act on it following the period set by Executive Order No. 2, s. 2016: within fifteen (15) working days of receipt, extendable when the request needs an extensive search or covers many records. If a request is denied in whole or in part, you will be told the reason in writing. Fees, if any, cover only the actual cost of reproduction and copying.</p>

<h3>Related Pages</h3>
<ul>
<li><a href="/page/transparency">Transparency</a></li>
<li><a href="/page/privacy-policy">Privacy Policy</a></li>
<li><a href="https://www.foi.gov.ph/" target="_blank" rel="noopener">eFOI portal</a>, for requests to national government agencies</li>
</ul>
HTML,
            ],
            [
                'slug'      => 'transparency',
                'name'      => 'Transparency',
                'menu_show' => 'policy',
                'content'   => <<<HTML
<p class="tpc-doc__lead">This page brings together public information on the mandate, governance, and accountability of Tarlac Provincial College.</p>

<h3>Charter</h3>
<p>The College was established by <strong>Provincial Ordinance No. 001-2026</strong>, the Tarlac Provincial College Charter, enacted by the Sangguniang Panlalawigan of Tarlac on 31 March 2026. Under Section 89, the ordinance took effect fifteen (15) days after its full publication in a newspaper of local circulation and its posting in conspicuous places in the province.</p>
<p><a href="/backend/uploads/documents/tpc-charter-provincial-ordinance-001-2026.pdf" target="_blank" rel="noopener">Download the charter (scanned PDF, 28 MB)</a></p>

<h3>Mandate</h3>
<p>Under Section 4 of the charter, the College primarily provides technological-vocational courses and higher professional instruction and training. It also undertakes research, extension services, and production activities in support of the development of the Province of Tarlac, provides progressive leadership in its areas of specialization, and serves as the hub of the education ecosystem in the province. See <a href="/page/purposes-of-the-college">Purposes of the College</a>.</p>

<h3>Governance</h3>
<p>The Governing Board acts as the Board of Trustees and is the highest policy-making body of the College (Sections 13 and 14). The Provincial Governor is Chairperson and the College President is Vice Chairperson. Members include the presidents of the faculty association and the supreme student council, the Chairperson of the Sangguniang Panlalawigan Committee on Education, representatives of the private sector, and the head of the Provincial Public Employment Service Office. Representatives of CHED, TESDA, and other national agencies serve as resource persons.</p>
<p>The Provincial Vice Governor and the Members of the Sangguniang Panlalawigan constitute the Board of Visitors of the College (Section 80).</p>

<h3>Financial Accountability</h3>
<p>The Treasurer of the Provincial Government of Tarlac is the ex-officio Treasurer of the College, and all accounts and expenses of the College are audited by the Commission on Audit (Section 77). Tuition and other fees, when collected, are approved by the Governing Board and follow government rules and regulations (Section 76).</p>

<h3>Reports</h3>
<p>The Governing Board files an annual report on the progress, conditions, and needs of the College with the Office of the Governor, copy furnished the Sangguniang Panlalawigan, CHED, and TESDA (Section 79). Annual reports, budgets, and procurement notices will be posted on this page when they become available.</p>

<h3>Citizen's Charter</h3>
<p>In line with Republic Act No. 11032, the Ease of Doing Business and Efficient Government Service Delivery Act of 2018, the College's Citizen's Charter, which lists its frontline services, requirements, fees, and processing times, will be published on this page once approved.</p>

<h3>Feedback and Complaints</h3>
<p>Comments, suggestions, and complaints about the College's services may be sent to {$mailto}. Complaints about government frontline services may also be reported to the national 8888 Citizens' Complaint Hotline.</p>

<h3>Related Pages</h3>
<ul>
<li><a href="/page/freedom-of-information">Freedom of Information</a></li>
<li><a href="/notices">Notices</a></li>
</ul>
HTML,
            ],
            [
                'slug'      => 'site-map',
                'name'      => 'Site Map',
                'menu_show' => 'policy',
                'content'   => <<<HTML
<p class="tpc-doc__lead">An overview of the pages on this website.</p>

<h3>The College</h3>
<ul>
<li><a href="/">Home</a></li>
<li><a href="/about">About</a></li>
<li><a href="/page/purposes-of-the-college">Purposes of the College</a></li>
<li><a href="/page/terms-of-admission">Terms of Admission</a></li>
<li><a href="/page/support">Support</a></li>
</ul>

<h3>News and Announcements</h3>
<ul>
<li><a href="/news">News</a></li>
<li><a href="/events">Events</a></li>
<li><a href="/notices">Notices</a></li>
</ul>

<h3>Online Services</h3>
<ul>
<li><a href="/online-admission">Online Admission</a></li>
<li><a href="/result">Result</a></li>
<li><a href="/login">Login (students, guardians, and staff)</a></li>
</ul>

<h3>Contact</h3>
<ul>
<li><a href="/contact">Contact Us</a></li>
</ul>

<h3>Policies and Public Information</h3>
<ul>
<li><a href="/page/privacy-policy">Privacy Policy</a></li>
<li><a href="/page/terms-of-use">Terms of Use</a></li>
<li><a href="/page/accessibility-statement">Accessibility Statement</a></li>
<li><a href="/page/freedom-of-information">Freedom of Information</a></li>
<li><a href="/page/transparency">Transparency</a></li>
</ul>
HTML,
            ],
        ];
    }

    private function notices(): array
    {
        $email = self::EMAIL;
        $mailto = '<a href="mailto:' . $email . '">' . $email . '</a>';
        $facebook = '<a href="https://www.facebook.com/tarlacprovincialcollegeofficial" target="_blank" rel="noopener">facebook.com/tarlacprovincialcollegeofficial</a>';

        return [
            [
                'title'       => 'Official email address and communication channels',
                'date'        => '2026-10-27',
                'description' => <<<HTML
<p>Tarlac Provincial College announces that its official email address is {$mailto}.</p>
<p>Official announcements of the College are posted on this website and on the official Facebook page of Tarlac Provincial College, {$facebook}.</p>
<p>The public is advised to rely on these channels for information on admission, scholarships, training, and other College matters, and to verify announcements from other sources with the College before acting on them.</p>
HTML,
            ],
            [
                'title'       => 'Data privacy notice for applicants, students, and website users',
                'date'        => '2026-10-27',
                'description' => <<<HTML
<p>Tarlac Provincial College processes the personal data of applicants, students, parents and guardians, personnel, and website users in accordance with Republic Act No. 10173, the Data Privacy Act of 2012.</p>
<p>The online admission and contact forms now ask you to confirm that you have read the College's Privacy Policy before you submit personal data. The policy explains what data is collected, why it is needed, who it may be shared with, how long it is kept, and how you can exercise your rights as a data subject.</p>
<p>Read the full <a href="/page/privacy-policy">Privacy Policy</a>. Questions and requests may be sent to {$mailto} with the subject "Data Privacy".</p>
HTML,
            ],
            [
                'title'       => 'Charter, policies, and public information now available online',
                'date'        => '2026-10-27',
                'description' => <<<HTML
<p>Tarlac Provincial College has published the following pages, available at the bottom of every page of this website:</p>
<ul>
<li><a href="/page/transparency">Transparency</a>, with the College's mandate, governance, and a downloadable copy of Provincial Ordinance No. 001-2026, the Tarlac Provincial College Charter;</li>
<li><a href="/page/freedom-of-information">Freedom of Information</a>, which explains how to request information from the College;</li>
<li><a href="/page/terms-of-use">Terms of Use</a> and <a href="/page/accessibility-statement">Accessibility Statement</a>;</li>
<li><a href="/page/site-map">Site Map</a>.</li>
</ul>
<p>Comments and suggestions on these pages may be sent to {$mailto}.</p>
HTML,
            ],
        ];
    }
}
