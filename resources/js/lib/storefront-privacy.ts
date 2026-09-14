export type PrivacyBlock =
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
    breadcrumbs: [
        { label: 'Home', href: 'home' as const },
        { label: 'Information', href: null },
        { label: 'Privacy Policy', href: 'current' as const },
    ],
    intro: [
        'This Privacy Policy explains how PuffMeMate (“we”, “us”, or “our”) collects, uses, stores, and shares personal information when you browse our storefront, create an account, place an order, request wholesale supply, or contact support.',
        'We operate a multi-branch online tobacconist experience across Australia. Because we sell age-restricted products, we also process information needed for identity checks, delivery handover, and legal compliance.',
        'Please read this policy carefully. By using our websites, apps, or services, you acknowledge the practices described here. If you do not agree, please do not use our services.',
    ],
    quickLinksLabel: 'Quick Links',
    quickLinks: [
        { label: 'What We Collect', id: 'information-we-collect' },
        { label: 'How We Collect', id: 'how-we-collect' },
        { label: 'Why We Use Information', id: 'why-we-use' },
        { label: 'Cookies', id: 'cookies' },
        { label: 'Orders & Delivery', id: 'branch-order-delivery' },
        { label: 'Age Verification', id: 'age-verification' },
        { label: 'Your Rights', id: 'your-rights' },
        { label: 'Contact Us', id: 'contact-us' },
    ],
    summaryTitle: 'Policy summary',
    summary: [
        'We collect account, order, delivery, device, and support information to run our storefront.',
        'Age-restricted retail requires identity and handover checks where applicable.',
        'We share data with trusted service providers (payments, delivery, hosting, messaging) under contracts.',
        'You can request access, correction, deletion, or marketing opt-out subject to law.',
        'Contact us anytime with privacy questions or complaints.',
    ],
    cta: {
        body: 'Need help understanding how we handle your data, or want to exercise a privacy request? Our team is ready to assist.',
        primaryLabel: 'Contact Us',
        secondaryLabel: 'FAQs',
    },
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
                text: 'Depending on how you use our services, we may collect:',
            },
            {
                type: 'list',
                items: [
                    'Identity and contact details (name, phone, email, delivery address, alias if provided).',
                    'Account credentials and security settings (including multi-factor preferences where enabled).',
                    'Order, cart, branch selection, fulfilment, and delivery status information.',
                    'Payment-related metadata from our processors (we do not store full card numbers on our servers).',
                    'Age-verification and handover records (for example OTP confirmation or ID check outcomes).',
                    'Device, browser, IP address, approximate location, and usage analytics.',
                    'Support messages, wholesale enquiry details, and feedback you send us.',
                ],
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
                text: 'We collect information directly from you when you register, check out, message support, or submit wholesale forms. We also collect information automatically through cookies, logs, and similar technologies when you use the site.',
            },
            {
                type: 'paragraph',
                text: 'In some cases we receive information from service providers such as payment gateways, delivery partners, fraud-prevention tools, and analytics platforms acting on our behalf.',
            },
        ],
    },
    {
        id: 'why-we-use',
        number: '04',
        title: 'Why We Collect And Use Information',
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
        id: 'legal-bases',
        number: '05',
        title: 'Legal Bases For Processing',
        blocks: [
            {
                type: 'paragraph',
                text: 'Where applicable privacy laws require a legal basis, we rely on one or more of the following: performance of a contract (fulfilling your order), legitimate interests (securing and improving our services), consent (certain cookies or marketing), and legal obligation (age-restricted retail and tax/compliance records).',
            },
            {
                type: 'paragraph',
                text: 'You may withdraw consent where processing is consent-based, without affecting the lawfulness of processing before withdrawal.',
            },
        ],
    },
    {
        id: 'cookies',
        number: '06',
        title: 'Cookies And Similar Technologies',
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
        id: 'branch-order-delivery',
        number: '07',
        title: 'Branch, Order And Delivery Data',
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
        id: 'age-verification',
        number: '08',
        title: 'Age Verification And Restricted Products',
        blocks: [
            {
                type: 'paragraph',
                text: 'We sell age-restricted products. We may request evidence that you (or an authorised adult recipient) are 18+ before completing sale or handover. This can include OTP confirmation at delivery and/or sighting of government photo ID by the courier or branch.',
            },
            {
                type: 'paragraph',
                text: 'If verification fails, we may refuse or cancel the order. We retain verification outcomes as needed for compliance and dispute handling.',
            },
        ],
    },
    {
        id: 'payments',
        number: '09',
        title: 'Payments And Transaction Records',
        blocks: [
            {
                type: 'paragraph',
                text: 'Payments are processed by third-party providers. We receive confirmation of payment status, limited card metadata (such as last four digits or wallet type where applicable), and transaction IDs needed for receipts, refunds, and audits.',
            },
            {
                type: 'paragraph',
                text: 'We do not store full payment card primary account numbers on PuffMeMate application servers.',
            },
        ],
    },
    {
        id: 'sharing',
        number: '10',
        title: 'Sharing With Service Providers',
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
        id: 'international-transfers',
        number: '11',
        title: 'International Transfers',
        blocks: [
            {
                type: 'paragraph',
                text: 'Some providers may process data outside Australia. Where we transfer personal information internationally, we take steps designed to ensure appropriate safeguards consistent with applicable Australian privacy expectations and contractual protections.',
            },
        ],
    },
    {
        id: 'retention',
        number: '12',
        title: 'How Long We Keep Information',
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
        id: 'security',
        number: '13',
        title: 'Security Measures',
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
        id: 'your-rights',
        number: '14',
        title: 'Your Rights',
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
                text: 'We may need to verify your identity before completing a request. Some requests may be refused where the law allows (for example, where deletion would break mandatory records).',
            },
        ],
    },
    {
        id: 'marketing',
        number: '15',
        title: 'Marketing Preferences',
        blocks: [
            {
                type: 'paragraph',
                text: 'With your permission or as otherwise allowed by law, we may send product updates, offers, or wholesale news. You can opt out of marketing emails using the unsubscribe link or by contacting us. Transactional messages about orders, delivery, and account security will continue as needed.',
            },
        ],
    },
    {
        id: 'children',
        number: '16',
        title: 'Children’s Privacy',
        blocks: [
            {
                type: 'paragraph',
                text: 'Our products and services are intended for adults aged 18 and over. We do not knowingly collect personal information from children. If you believe a minor has provided information, contact us and we will take appropriate steps to delete it.',
            },
        ],
    },
    {
        id: 'third-party-links',
        number: '17',
        title: 'Third-Party Links',
        blocks: [
            {
                type: 'paragraph',
                text: 'Our site may link to third-party websites, apps, or payment portals. Those services have their own privacy practices. We are not responsible for content or privacy controls on third-party sites you choose to visit.',
            },
        ],
    },
    {
        id: 'changes',
        number: '18',
        title: 'Changes To This Policy',
        blocks: [
            {
                type: 'paragraph',
                text: 'We may update this Privacy Policy from time to time to reflect product, legal, or operational changes. The updated version will be posted on this page with a revised effective date. Continued use of our services after changes means you acknowledge the updated policy.',
            },
        ],
    },
    {
        id: 'complaints',
        number: '19',
        title: 'Complaints',
        blocks: [
            {
                type: 'paragraph',
                text: 'If you have a privacy concern, please contact us first so we can investigate and respond. If you remain unsatisfied, you may contact the Office of the Australian Information Commissioner (OAIC) or another relevant regulator.',
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
