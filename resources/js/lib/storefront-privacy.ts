export type PrivacyBlock =
    | { type: 'subheading'; text: string }
    | { type: 'paragraph'; text: string }
    | { type: 'list'; items: string[] };

export type PrivacySection = {
    id: string;
    number: string;
    title: string;
    blocks: PrivacyBlock[];
};

export const privacyPage = {
    title: 'Privacy Policy',
    eyebrow: 'Legal & Discretion Protocol · Australian Compliance',
    breadcrumbs: [
        { label: 'Home', href: 'home' as const },
        { label: 'Legal & Governance', href: null },
        { label: 'Privacy Policy', href: 'current' as const },
    ],
    intro: 'At PuffMeMate, we respect your privacy and are committed to handling your personal information responsibly. Learn how we collect, use, store, disclose and protect personal information across our website, PWA, branch dispatch, and courier logistics network.',
    versionLabel: 'Version 2.4 · APP & Privacy Act 1988',
    updatedAt: 'Last Updated: October 2024',
    applicableEntity: 'Applicable Entity: PuffMeMate Australia',
    summaryPoints: [
        {
            title: 'What We Collect',
            body: 'Account credentials, order records, delivery coordinates, age verification hashes, and minimal telemetry required for dispatch.',
            id: 'information-we-collect',
        },
        {
            title: 'Why We Collect It',
            body: 'Immediate local order fulfillment, branch stock routing, customer support, and statutory Australian age compliance.',
            id: 'why-we-use',
        },
        {
            title: 'Who Receives It',
            body: 'Assigned localized branch staff, vetted delivery partners under VoIP masking, and PCI-DSS Tier 1 payment processors.',
            id: 'who-may-receive',
        },
        {
            title: 'Your Privacy Rights',
            body: 'Full rights of access, correction, erasure requests, and Australian Information Commissioner (OAIC) escalation mechanisms.',
            id: 'your-privacy-rights',
        },
    ],
};

export const privacySections: PrivacySection[] = [
    {
        id: 'who-we-are',
        number: '01',
        title: 'Who We Are',
        blocks: [
            {
                type: 'paragraph',
                text: 'PuffMeMate is an Australia-wide online tobacconist connecting customers with local branch inventory for cigarettes, tobacco, vapes, nicotine pouches, and related accessories.',
            },
            {
                type: 'paragraph',
                text: 'For privacy requests, email privacy@puffmemate.demo or use the Contact page. Please include enough detail for us to verify your identity and locate your records.',
            },
        ],
    },
    {
        id: 'information-we-collect',
        number: '02',
        title: 'Information We Collect',
        blocks: [
            {
                type: 'paragraph',
                text: 'Depending on how you use our services, we may collect the following categories of information:',
            },
            {
                type: 'subheading',
                text: 'A. Account Information',
            },
            {
                type: 'paragraph',
                text: 'Name, phone, email, delivery address, alias (if provided), account credentials, and security settings including multi-factor preferences where enabled.',
            },
            {
                type: 'subheading',
                text: 'B. Order Information',
            },
            {
                type: 'paragraph',
                text: 'Cart contents, selected branch, fulfilment method, delivery slot, order status, and communications about your purchase.',
            },
            {
                type: 'subheading',
                text: 'C. Billing Information',
            },
            {
                type: 'paragraph',
                text: 'Payment status and limited transaction metadata from our processors. We do not store full card numbers on PuffMeMate servers.',
            },
            {
                type: 'subheading',
                text: 'D. Age Verification & Restricted Retail',
            },
            {
                type: 'paragraph',
                text: 'Outcomes of age checks and handover confirmation required for age-restricted products (for example OTP confirmation or ID check results).',
            },
            {
                type: 'subheading',
                text: 'E. Device & Usage Information',
            },
            {
                type: 'paragraph',
                text: 'Device, browser, IP address, approximate location, cookies, analytics events, support messages, wholesale enquiry details, and feedback you send us.',
            },
        ],
    },
    {
        id: 'how-we-collect',
        number: '03',
        title: 'How We Collect Your Information',
        blocks: [
            {
                type: 'paragraph',
                text: 'We collect information in the following ways:',
            },
            {
                type: 'list',
                items: [
                    'Directly from you when you register, check out, message support, or submit wholesale forms.',
                    'Automatically through cookies, logs, and similar technologies when you use the site.',
                    'From service providers such as payment gateways, delivery partners, fraud-prevention tools, and analytics platforms acting on our behalf.',
                ],
            },
        ],
    },
    {
        id: 'why-we-use',
        number: '04',
        title: 'Why We Use Your Information',
        blocks: [
            {
                type: 'paragraph',
                text: 'We use personal information to:',
            },
            {
                type: 'list',
                items: [
                    'Provide, personalise, and improve browsing, ordering, and delivery experiences.',
                    'Match orders to the right branch inventory and communicate fulfilment updates.',
                    'Process payments, refunds, chargebacks, and accounting records.',
                    'Verify age eligibility and complete secure handover for restricted products.',
                    'Detect fraud, abuse, and security incidents.',
                    'Respond to enquiries, wholesale applications, and complaints.',
                    'Send service notices and, where permitted, marketing communications.',
                    'Meet legal, regulatory, and record-keeping obligations.',
                ],
            },
        ],
    },
    {
        id: 'order-delivery',
        number: '05',
        title: 'Order & Delivery Information',
        blocks: [
            {
                type: 'paragraph',
                text: 'Because fulfilment is branch-aware, order records may include the selected branch, stock reservation status, delivery method, scheduled slot, courier assignment, and live tracking events.',
            },
            {
                type: 'paragraph',
                text: 'Drivers and branch staff only receive the information needed to prepare, dispatch, and deliver your order. Tracking updates may be shown in your account or messaging channels.',
            },
        ],
    },
    {
        id: 'location',
        number: '06',
        title: 'Location Information',
        blocks: [
            {
                type: 'paragraph',
                text: 'We may use approximate location derived from your IP address, delivery address, or device permissions to suggest nearby branches, estimate delivery windows, and improve logistics.',
            },
            {
                type: 'paragraph',
                text: 'Precise device location is only used where you grant permission or where it is reasonably necessary to complete delivery.',
            },
        ],
    },
    {
        id: 'cookies',
        number: '07',
        title: 'Cookies & Similar Technologies',
        blocks: [
            {
                type: 'paragraph',
                text: 'We use cookies and similar technologies to keep you signed in, remember preferences, measure performance, and understand how customers use the storefront.',
            },
            {
                type: 'list',
                items: [
                    'Essential cookies — required for cart, checkout, and security.',
                    'Functional cookies — remember settings such as branch or language preferences.',
                    'Analytics cookies — help us improve page performance and product discovery.',
                    'Marketing cookies — used only where allowed and typically with consent controls.',
                ],
            },
            {
                type: 'paragraph',
                text: 'You can control cookies through your browser settings. Blocking essential cookies may prevent checkout or account features from working correctly.',
            },
        ],
    },
    {
        id: 'hold-protect',
        number: '08',
        title: 'How We Hold & Protect Your Information',
        blocks: [
            {
                type: 'paragraph',
                text: 'We use administrative, technical, and organisational measures designed to protect personal information against unauthorised access, loss, misuse, or alteration. These may include access controls, encryption in transit, monitoring, and staff confidentiality obligations.',
            },
            {
                type: 'paragraph',
                text: 'No method of transmission or storage is completely secure. Please protect your account credentials and notify us promptly of any suspected unauthorised access.',
            },
        ],
    },
    {
        id: 'who-may-receive',
        number: '09',
        title: 'Who May Receive Your Information',
        blocks: [
            {
                type: 'paragraph',
                text: 'We share personal information with trusted providers who help us operate the business, including:',
            },
            {
                type: 'list',
                items: [
                    'Hosting, cloud infrastructure, and security vendors.',
                    'Payment processors and fraud-prevention partners.',
                    'Delivery, logistics, and mapping/ETA providers.',
                    'Customer messaging, email, and notification services.',
                    'Analytics and product-improvement tools.',
                    'Professional advisers (legal, accounting) where required.',
                ],
            },
            {
                type: 'paragraph',
                text: 'We require service providers to protect personal information and use it only for contracted purposes. We may also disclose information if required by law, court order, or to protect rights, safety, and property.',
            },
        ],
    },
    {
        id: 'overseas-disclosure',
        number: '10',
        title: 'Overseas Disclosure',
        blocks: [
            {
                type: 'paragraph',
                text: 'Some providers may process data outside Australia. Where we transfer personal information internationally, we take steps designed to ensure appropriate safeguards consistent with applicable Australian privacy expectations and contractual protections.',
            },
        ],
    },
    {
        id: 'retention',
        number: '11',
        title: 'How Long We Keep Your Information',
        blocks: [
            {
                type: 'paragraph',
                text: 'We keep personal information only as long as needed for the purposes described in this policy, including order history, warranty/DOA handling, dispute resolution, tax and regulatory retention, and security investigations.',
            },
            {
                type: 'paragraph',
                text: 'When information is no longer required, we delete or de-identify it, unless a longer period is required or permitted by law.',
            },
        ],
    },
    {
        id: 'accessing',
        number: '12',
        title: 'Accessing Your Information',
        blocks: [
            {
                type: 'paragraph',
                text: 'You may request access to the personal information we hold about you. Email privacy@puffmemate.demo or use the Contact page. We may need to verify your identity before releasing records.',
            },
        ],
    },
    {
        id: 'correcting',
        number: '13',
        title: 'Correcting Your Information',
        blocks: [
            {
                type: 'paragraph',
                text: 'If your account or order details are inaccurate or incomplete, you can update them in your account where available, or contact us at privacy@puffmemate.demo so we can correct the information.',
            },
        ],
    },
    {
        id: 'your-privacy-rights',
        number: '14',
        title: 'Your Privacy Rights',
        blocks: [
            {
                type: 'paragraph',
                text: 'Subject to Australian privacy law and applicable exemptions, you may request to:',
            },
            {
                type: 'list',
                items: [
                    'Access the personal information we hold about you.',
                    'Correct inaccurate or incomplete information.',
                    'Ask us to delete information where we no longer need it.',
                    'Object to or restrict certain processing.',
                    'Withdraw consent for consent-based activities such as marketing.',
                    'Lodge a complaint about how we handle your information.',
                ],
            },
            {
                type: 'paragraph',
                text: 'Some requests may be refused where the law allows (for example, where deletion would break mandatory records).',
            },
        ],
    },
    {
        id: 'privacy-complaints',
        number: '15',
        title: 'Privacy Complaints',
        blocks: [
            {
                type: 'paragraph',
                text: 'If you have a privacy concern, please contact us first at privacy@puffmemate.demo so we can investigate and respond. If you remain unsatisfied, you may contact the Office of the Australian Information Commissioner (OAIC) or another relevant regulator.',
            },
        ],
    },
    {
        id: 'account-security',
        number: '16',
        title: 'Account Security',
        blocks: [
            {
                type: 'paragraph',
                text: 'You are responsible for keeping your login credentials confidential and for activity under your account. Enable available security features and contact us immediately if you suspect unauthorised access.',
            },
        ],
    },
    {
        id: 'third-party-services',
        number: '17',
        title: 'Third-Party Services',
        blocks: [
            {
                type: 'paragraph',
                text: 'Our site may link to third-party websites, apps, or payment portals. Those services have their own privacy practices. We are not responsible for content or privacy controls on third-party sites you choose to visit.',
            },
        ],
    },
    {
        id: 'children',
        number: '18',
        title: 'Children And Other Protected Persons',
        blocks: [
            {
                type: 'paragraph',
                text: 'Our products and services are intended for adults aged 18 and over. We do not knowingly collect personal information from children. If you believe a minor has provided information, contact privacy@puffmemate.demo and we will take appropriate steps to delete it.',
            },
        ],
    },
    {
        id: 'changes',
        number: '19',
        title: 'Changes To This Privacy Policy',
        blocks: [
            {
                type: 'paragraph',
                text: 'We may update this Privacy Policy from time to time to reflect product, legal, or operational changes. The updated version will be posted on this page with a revised effective date. Continued use of our services after changes means you acknowledge the updated policy.',
            },
        ],
    },
    {
        id: 'contact-us',
        number: '20',
        title: 'Contact Us',
        blocks: [
            {
                type: 'paragraph',
                text: 'For privacy questions, access requests, or complaints, contact PuffMeMate via the Contact page, email privacy@puffmemate.demo, or phone the support number listed on our Contact page during business hours.',
            },
            {
                type: 'paragraph',
                text: 'Please include your name, account email or order reference (if relevant), and a clear description of your request so we can respond efficiently.',
            },
        ],
    },
];
