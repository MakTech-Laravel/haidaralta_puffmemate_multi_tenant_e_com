<?php

namespace App\Support\Cms;

final class CmsPageDefaults
{
    /** @return list<string> */
    public static function slugs(): array
    {
        return ['about', 'contact', 'privacy', 'terms', 'faqs', 'wholesale'];
    }

    /**
     * @return array{title: string, meta_title: string|null, meta_description: string|null, is_published: bool, payload: array<string, mixed>}
     */
    public static function definition(string $slug): array
    {
        return match ($slug) {
            'about' => [
                'title' => 'About Us',
                'meta_title' => 'About Us',
                'meta_description' => 'Fresh product, fast service — always there for customers across Australia.',
                'is_published' => true,
                'payload' => [
                    'hero' => [
                        'title' => 'About Us',
                        'subtitle' => 'Fresh product, fast service — always there for customers across Australia.',
                        'image' => '/images/storefront/hero-1.jpg',
                    ],
                    'who_we_are' => [
                        'eyebrow' => 'Who we are',
                        'heading' => 'More Than An Online Store',
                        'body' => '{name} connects you with trusted local branches for cigarettes, tobacco, vapes and more — with instant delivery, clear stock, and a simple checkout.',
                        'mediaImage' => '/images/storefront/about.jpg',
                        'mediaTitle' => 'Connected Platform',
                        'mediaCaption' => 'Download App Now. Shopping, Branches, Delivery.',
                        'features' => [
                            [
                                'title' => 'Online Shopping',
                                'description' => 'Browse categories and products from your phone or desktop in minutes.',
                            ],
                            [
                                'title' => 'Smooth Store Ordering',
                                'description' => 'Pick a nearby branch, reserve stock, and check out without friction.',
                            ],
                            [
                                'title' => 'Multi-Branch Stock',
                                'description' => 'See what is available where you are — the right branch, the right inventory.',
                            ],
                            [
                                'title' => 'Fast Local Delivery',
                                'description' => 'Same-day local drops and Australia-wide shipping when you need it.',
                            ],
                        ],
                    ],
                    'how_it_works' => [
                        'eyebrow' => 'How it works',
                        'heading' => 'From Selection To Delivery — All In One Experience',
                        'body' => 'A clear path from choosing your branch to watching your order arrive — built for speed and certainty.',
                        'steps' => [
                            [
                                'number' => '01',
                                'title' => 'Choose Your Location',
                                'description' => 'Set where you want products delivered or collected.',
                                'icon' => 'map-pin',
                            ],
                            [
                                'number' => '02',
                                'title' => 'Select Branch',
                                'description' => 'Pick the nearest branch with the stock you need.',
                                'icon' => 'store',
                            ],
                            [
                                'number' => '03',
                                'title' => 'Browse Products',
                                'description' => 'Explore cigarettes, tobacco, vapes and accessories.',
                                'icon' => 'shopping-bag',
                            ],
                            [
                                'number' => '04',
                                'title' => 'Add To Cart',
                                'description' => 'Build your order and reserve availability instantly.',
                                'icon' => 'shopping-cart',
                            ],
                            [
                                'number' => '05',
                                'title' => 'Checkout',
                                'description' => 'Confirm details, delivery method and payment at door.',
                                'icon' => 'wallet',
                            ],
                            [
                                'number' => '06',
                                'title' => 'Order Processing',
                                'description' => 'Your branch prepares the order carefully and quickly.',
                                'icon' => 'package',
                            ],
                            [
                                'number' => '07',
                                'title' => 'Driver Assigned',
                                'description' => 'A local driver is matched for your delivery window.',
                                'icon' => 'user-round',
                            ],
                            [
                                'number' => '08',
                                'title' => 'Live Delivery Tracking',
                                'description' => 'Follow status updates from dispatch to doorstep.',
                                'icon' => 'truck',
                            ],
                            [
                                'number' => '09',
                                'title' => 'Order Delivered',
                                'description' => 'Receive your order with discreet packaging and ID check.',
                                'icon' => 'check-circle-2',
                            ],
                        ],
                    ],
                    'multi_branch' => [
                        'eyebrow' => 'Multi branch inventory',
                        'heading' => 'Your Order. The Right Branch. The Right Stock.',
                        'body' => 'We weight distance, delivery location and live inventory so every order routes to a branch that can fulfil it — not a generic warehouse promise.',
                        'promise' => 'Product availability and pricing can vary by branch.',
                        'factors' => [
                            [
                                'number' => '01',
                                'title' => 'Distance',
                                'description' => 'Prefer branches closest to your delivery pin.',
                            ],
                            [
                                'number' => '02',
                                'title' => 'Delivery Location',
                                'description' => 'Match coverage zones for same-day local drops.',
                            ],
                            [
                                'number' => '03',
                                'title' => 'Live Stock',
                                'description' => 'Only show branches that can fulfil your cart.',
                            ],
                            [
                                'number' => '04',
                                'title' => 'Customer Preference',
                                'description' => 'Remember your favourite branch when it fits.',
                            ],
                            [
                                'number' => '05',
                                'title' => 'Fulfilment Speed',
                                'description' => 'Prioritise branches ready to pick and dispatch.',
                            ],
                        ],
                    ],
                    'delivery' => [
                        'eyebrow' => 'Delivery experience',
                        'heading' => 'Stay Connected From Order To Doorstep',
                        'paragraphs' => [
                            'Track every stage after checkout — from processing and preparation through driver assignment and out-for-delivery.',
                            'Clear status updates keep you informed without chasing support, so you know exactly when your order is on the way.',
                        ],
                        'statuses' => [
                            [
                                'label' => 'Processing',
                                'state' => 'done',
                            ],
                            [
                                'label' => 'Preparing',
                                'state' => 'done',
                            ],
                            [
                                'label' => 'Driver Assigned',
                                'state' => 'done',
                            ],
                            [
                                'label' => 'Out For Delivery',
                                'state' => 'active',
                            ],
                            [
                                'label' => 'Arrived',
                                'state' => 'pending',
                            ],
                            [
                                'label' => 'Delivered',
                                'state' => 'pending',
                            ],
                        ],
                    ],
                    'cta' => [
                        'eyebrow' => 'Start shopping',
                        'heading' => 'Ready For Your Next Order?',
                        'body' => 'Browse the catalogue, pick your branch, and get fresh product delivered with the same care as in-store.',
                        'primaryLabel' => 'Shop Now',
                        'secondaryLabel' => 'Our Products',
                    ],
                ],
            ],
            'contact' => [
                'title' => 'Contact Us',
                'meta_title' => 'Contact Us',
                'meta_description' => 'Passionate about quality, design, and delivering value to customers',
                'is_published' => true,
                'payload' => [
                    'hero' => [
                        'title' => 'Contact Us',
                        'subtitle' => 'Passionate about quality, design, and delivering value to customers',
                        'image' => '/images/storefront/hero-1.jpg',
                    ],
                    'info' => [
                        'title' => 'Contact Information',
                        'subtitle' => 'Say something to start a live chat!',
                    ],
                    'form' => [
                        'firstName' => 'First Name',
                        'lastName' => 'Last Name',
                        'email' => 'Email',
                        'phone' => 'Phone Number',
                        'message' => 'Message',
                        'lastNamePlaceholder' => 'Doe',
                        'phonePlaceholder' => '+1 012 3456 789',
                        'messagePlaceholder' => 'Write your message..',
                        'submitLabel' => 'Send Message',
                    ],
                ],
            ],
            'privacy' => [
                'title' => 'Privacy Policy',
                'meta_title' => 'Privacy Policy',
                'meta_description' => 'At PuffMeMate, we respect your privacy and are committed to handling your personal information responsibly.',
                'is_published' => true,
                'payload' => [
                    'eyebrow' => 'Legal & Discretion Protocol · Australian Compliance',
                    'intro' => 'At PuffMeMate, we respect your privacy and are committed to handling your personal information responsibly. Learn how we collect, use, store, disclose and protect personal information across our website, PWA, branch dispatch, and courier logistics network.',
                    'versionLabel' => 'Version 2.4 · APP & Privacy Act 1988',
                    'updatedAt' => '2024-10-01',
                    'applicableEntity' => 'Applicable Entity: PuffMeMate Australia',
                    'summaryPoints' => [
                        [
                            'id' => 'information-we-collect',
                            'title' => 'What We Collect',
                            'body' => 'Account credentials, order records, delivery coordinates, age verification hashes, and minimal telemetry required for dispatch.',
                        ],
                        [
                            'id' => 'why-we-use',
                            'title' => 'Why We Collect It',
                            'body' => 'Immediate local order fulfillment, branch stock routing, customer support, and statutory Australian age compliance.',
                        ],
                        [
                            'id' => 'who-may-receive',
                            'title' => 'Who Receives It',
                            'body' => 'Assigned localized branch staff, vetted delivery partners under VoIP masking, and PCI-DSS Tier 1 payment processors.',
                        ],
                        [
                            'id' => 'your-privacy-rights',
                            'title' => 'Your Privacy Rights',
                            'body' => 'Full rights of access, correction, erasure requests, and Australian Information Commissioner (OAIC) escalation mechanisms.',
                        ],
                    ],
                    'body_html' => '<h2>01. Who We Are</h2><p>PuffMeMate is an Australia-wide online tobacconist connecting customers with local branch inventory for cigarettes, tobacco, vapes, nicotine pouches, and related accessories.</p><p>For privacy requests, email privacy@puffmemate.demo or use the Contact page. Please include enough detail for us to verify your identity and locate your records.</p><h2>02. Information We Collect</h2><p>Depending on how you use our services, we may collect the following categories of information:</p><h3>A. Account Information</h3><p>Name, phone, email, delivery address, alias (if provided), account credentials, and security settings including multi-factor preferences where enabled.</p><h3>B. Order Information</h3><p>Cart contents, selected branch, fulfilment method, delivery slot, order status, and communications about your purchase.</p><h3>C. Billing Information</h3><p>Payment status and limited transaction metadata from our processors. We do not store full card numbers on PuffMeMate servers.</p><h3>D. Age Verification &amp; Restricted Retail</h3><p>Outcomes of age checks and handover confirmation required for age-restricted products (for example OTP confirmation or ID check results).</p><h3>E. Device &amp; Usage Information</h3><p>Device, browser, IP address, approximate location, cookies, analytics events, support messages, wholesale enquiry details, and feedback you send us.</p><h2>03. How We Collect Your Information</h2><p>We collect information in the following ways:</p><ul><li>Directly from you when you register, check out, message support, or submit wholesale forms.</li><li>Automatically through cookies, logs, and similar technologies when you use the site.</li><li>From service providers such as payment gateways, delivery partners, fraud-prevention tools, and analytics platforms acting on our behalf.</li></ul><h2>04. Why We Use Your Information</h2><p>We use personal information to:</p><ul><li>Provide, personalise, and improve browsing, ordering, and delivery experiences.</li><li>Match orders to the right branch inventory and communicate fulfilment updates.</li><li>Process payments, refunds, chargebacks, and accounting records.</li><li>Verify age eligibility and complete secure handover for restricted products.</li><li>Detect fraud, abuse, and security incidents.</li><li>Respond to enquiries, wholesale applications, and complaints.</li><li>Send service notices and, where permitted, marketing communications.</li><li>Meet legal, regulatory, and record-keeping obligations.</li></ul><h2>05. Order &amp; Delivery Information</h2><p>Because fulfilment is branch-aware, order records may include the selected branch, stock reservation status, delivery method, scheduled slot, courier assignment, and live tracking events.</p><p>Drivers and branch staff only receive the information needed to prepare, dispatch, and deliver your order. Tracking updates may be shown in your account or messaging channels.</p><h2>06. Location Information</h2><p>We may use approximate location derived from your IP address, delivery address, or device permissions to suggest nearby branches, estimate delivery windows, and improve logistics.</p><p>Precise device location is only used where you grant permission or where it is reasonably necessary to complete delivery.</p><h2>07. Cookies &amp; Similar Technologies</h2><p>We use cookies and similar technologies to keep you signed in, remember preferences, measure performance, and understand how customers use the storefront.</p><ul><li>Essential cookies — required for cart, checkout, and security.</li><li>Functional cookies — remember settings such as branch or language preferences.</li><li>Analytics cookies — help us improve page performance and product discovery.</li><li>Marketing cookies — used only where allowed and typically with consent controls.</li></ul><p>You can control cookies through your browser settings. Blocking essential cookies may prevent checkout or account features from working correctly.</p><h2>08. How We Hold &amp; Protect Your Information</h2><p>We use administrative, technical, and organisational measures designed to protect personal information against unauthorised access, loss, misuse, or alteration. These may include access controls, encryption in transit, monitoring, and staff confidentiality obligations.</p><p>No method of transmission or storage is completely secure. Please protect your account credentials and notify us promptly of any suspected unauthorised access.</p><h2>09. Who May Receive Your Information</h2><p>We share personal information with trusted providers who help us operate the business, including:</p><ul><li>Hosting, cloud infrastructure, and security vendors.</li><li>Payment processors and fraud-prevention partners.</li><li>Delivery, logistics, and mapping/ETA providers.</li><li>Customer messaging, email, and notification services.</li><li>Analytics and product-improvement tools.</li><li>Professional advisers (legal, accounting) where required.</li></ul><p>We require service providers to protect personal information and use it only for contracted purposes. We may also disclose information if required by law, court order, or to protect rights, safety, and property.</p><h2>10. Overseas Disclosure</h2><p>Some providers may process data outside Australia. Where we transfer personal information internationally, we take steps designed to ensure appropriate safeguards consistent with applicable Australian privacy expectations and contractual protections.</p><h2>11. How Long We Keep Your Information</h2><p>We keep personal information only as long as needed for the purposes described in this policy, including order history, warranty/DOA handling, dispute resolution, tax and regulatory retention, and security investigations.</p><p>When information is no longer required, we delete or de-identify it, unless a longer period is required or permitted by law.</p><h2>12. Accessing Your Information</h2><p>You may request access to the personal information we hold about you. Email privacy@puffmemate.demo or use the Contact page. We may need to verify your identity before releasing records.</p><h2>13. Correcting Your Information</h2><p>If your account or order details are inaccurate or incomplete, you can update them in your account where available, or contact us at privacy@puffmemate.demo so we can correct the information.</p><h2>14. Your Privacy Rights</h2><p>Subject to Australian privacy law and applicable exemptions, you may request to:</p><ul><li>Access the personal information we hold about you.</li><li>Correct inaccurate or incomplete information.</li><li>Ask us to delete information where we no longer need it.</li><li>Object to or restrict certain processing.</li><li>Withdraw consent for consent-based activities such as marketing.</li><li>Lodge a complaint about how we handle your information.</li></ul><p>Some requests may be refused where the law allows (for example, where deletion would break mandatory records).</p><h2>15. Privacy Complaints</h2><p>If you have a privacy concern, please contact us first at privacy@puffmemate.demo so we can investigate and respond. If you remain unsatisfied, you may contact the Office of the Australian Information Commissioner (OAIC) or another relevant regulator.</p><h2>16. Account Security</h2><p>You are responsible for keeping your login credentials confidential and for activity under your account. Enable available security features and contact us immediately if you suspect unauthorised access.</p><h2>17. Third-Party Services</h2><p>Our site may link to third-party websites, apps, or payment portals. Those services have their own privacy practices. We are not responsible for content or privacy controls on third-party sites you choose to visit.</p><h2>18. Children And Other Protected Persons</h2><p>Our products and services are intended for adults aged 18 and over. We do not knowingly collect personal information from children. If you believe a minor has provided information, contact privacy@puffmemate.demo and we will take appropriate steps to delete it.</p><h2>19. Changes To This Privacy Policy</h2><p>We may update this Privacy Policy from time to time to reflect product, legal, or operational changes. The updated version will be posted on this page with a revised effective date. Continued use of our services after changes means you acknowledge the updated policy.</p><h2>20. Contact Us</h2><p>For privacy questions, access requests, or complaints, contact PuffMeMate via the Contact page, email privacy@puffmemate.demo, or phone the support number listed on our Contact page during business hours.</p><p>Please include your name, account email or order reference (if relevant), and a clear description of your request so we can respond efficiently.</p>',
                ],
            ],
            'terms' => [
                'title' => 'Terms of Service',
                'meta_title' => 'Terms of Service',
                'meta_description' => 'Terms governing use of PuffMeMate websites, apps, and related services.',
                'is_published' => true,
                'payload' => [
                    'eyebrow' => 'Legal & Governance · Australian Compliance',
                    'intro' => 'These Terms of Service govern your use of PuffMeMate websites, apps, branch fulfilment, and delivery services across Australia.',
                    'versionLabel' => 'Version 1.0 · Terms of Service',
                    'updatedAt' => '2024-10-01',
                    'applicableEntity' => 'Applicable Entity: PuffMeMate Australia',
                    'summaryPoints' => [
                        [
                            'id' => 'eligibility',
                            'title' => 'Eligibility',
                            'body' => 'Services are intended for adults aged 18+ who can legally purchase age-restricted products.',
                        ],
                        [
                            'id' => 'orders',
                            'title' => 'Orders & Fulfilment',
                            'body' => 'Availability and pricing vary by branch; orders are accepted when the assigned branch confirms them.',
                        ],
                        [
                            'id' => 'delivery',
                            'title' => 'Delivery & Handover',
                            'body' => 'Age-restricted deliveries may require OTP confirmation and photo ID at handover.',
                        ],
                        [
                            'id' => 'consumer-law',
                            'title' => 'Consumer Rights',
                            'body' => 'Nothing in these terms excludes rights that cannot be limited under Australian Consumer Law.',
                        ],
                    ],
                    'body_html' => '<h2>1. Agreement To Terms</h2>
<p>By accessing or using PuffMeMate websites, apps, and related services, you agree to these Terms of Service. If you do not agree, do not use our services.</p>
<h2>2. Eligibility</h2>
<p>You must be at least 18 years of age and legally able to purchase age-restricted products in your jurisdiction. We may refuse service where age or identity cannot be verified.</p>
<h2>3. Orders And Fulfilment</h2>
<p>Product availability, pricing, and delivery options may vary by branch. Placing an order constitutes an offer to purchase; acceptance occurs when the assigned branch confirms the order.</p>
<p>We reserve the right to cancel orders for stock errors, suspected fraud, regulatory restrictions, or inability to complete age verification or secure handover.</p>
<h2>4. Delivery And Collection</h2>
<p>Delivery timeframes are estimates. Risk of loss passes on confirmed handover or collection. Age-restricted products may require OTP confirmation and/or photo ID at delivery.</p>
<h2>5. Payments And Pricing</h2>
<p>Prices are shown in Australian dollars unless stated otherwise and may include applicable taxes. Payment is processed through third-party providers; we do not store full card numbers on our servers.</p>
<h2>6. Returns And DOA</h2>
<p>Eligible dead-on-arrival claims should be reported promptly with supporting evidence. Statutory consumer rights under Australian Consumer Law are not excluded by these terms.</p>
<h2>7. Acceptable Use</h2>
<p>You must not misuse the platform, attempt unauthorised access, resell inventory without approval, or use our services for unlawful purposes.</p>
<h2>8. Limitation Of Liability</h2>
<p>To the maximum extent permitted by law, PuffMeMate is not liable for indirect or consequential loss arising from use of the services. Nothing in these terms limits rights that cannot be excluded under Australian Consumer Law.</p>
<h2>9. Changes</h2>
<p>We may update these Terms of Service from time to time. Continued use after changes constitutes acceptance of the revised terms.</p>
<h2>10. Contact</h2>
<p>Questions about these terms can be sent via the Contact page or the support channels published on our website.</p>',
                ],
            ],
            'faqs' => [
                'title' => 'FAQs',
                'meta_title' => 'FAQs',
                'meta_description' => 'Find clear answers regarding our branch-aware ordering, live dispatch tracking, discreet packaging, verification, and customer support.',
                'is_published' => true,
                'payload' => [
                    'hero' => [
                        'badge' => 'Help & Knowledge Desk / 12 Hubs Connected',
                        'title' => 'How can we help you today?',
                        'subtitle' => 'Find clear answers regarding our branch-aware ordering, live dispatch tracking, discreet packaging, verification, and customer support.',
                    ],
                    'categories' => [
                        [
                            'id' => 'multi-branch',
                            'title' => 'Multi-Branch & Local Inventory',
                            'icon' => 'map-pinned',
                            'items' => [
                                [
                                    'id' => 'branch-aware',
                                    'question' => 'How does branch-aware ordering work?',
                                    'answer' => 'When you choose a delivery location, we show stock and pricing from nearby branches that can fulfil your cart. You pick a branch before checkout so availability is reserved from that store.',
                                ],
                                [
                                    'id' => 'price-availability',
                                    'question' => 'Can prices or product availability differ between branches?',
                                    'answer' => 'Yes. Each branch manages its own inventory and pricing. What you see in cart and checkout always reflects the branch you selected for that order.',
                                ],
                                [
                                    'id' => 'change-branch',
                                    'question' => 'Can I change my branch after placing an order?',
                                    'answer' => 'Once an order is accepted by a branch, the fulfilment location is locked. Cancel and reorder from another branch if you need a different store — subject to stock and cut-off times.',
                                ],
                            ],
                        ],
                        [
                            'id' => 'delivery',
                            'title' => 'Delivery & Real-Time Tracking',
                            'icon' => 'truck',
                            'items' => [
                                [
                                    'id' => 'live-tracking',
                                    'question' => 'How does live order tracking work?',
                                    'answer' => 'From the moment your local branch accepts the order, you can follow its full lifecycle: Processing → Preparing → Driver Assigned → On The Way → Arrived. Once the courier departs, you receive a real-time GPS telemetry map showing the courier\'s route and exact minute ETA.',
                                    'defaultOpen' => true,
                                ],
                                [
                                    'id' => 'stages-timeframes',
                                    'question' => 'What are the typical delivery stages and timeframes?',
                                    'answer' => 'Same-day local deliveries usually move from acceptance to out-for-delivery within hours, depending on your slot. Australia-wide parcels typically arrive in 2–5 business days after dispatch.',
                                ],
                                [
                                    'id' => 'courier-contact',
                                    'question' => 'Can I communicate directly with my courier during transit?',
                                    'answer' => 'When your order is out for delivery, in-app updates and support channels share status and ETA. Direct courier chat is available on eligible local runs where the driver has messaging enabled.',
                                ],
                            ],
                        ],
                        [
                            'id' => 'privacy',
                            'title' => 'Privacy, Discretion & Packaging',
                            'icon' => 'package-check',
                            'items' => [
                                [
                                    'id' => 'discreet-packaging',
                                    'question' => 'Is packaging completely discreet?',
                                    'answer' => 'All customer shipments arrive in plain, unmarked matte luxury outer cartons with no external logos, brand references, or product indicators. Deliveries are indistinguishable from standard courier parcels.',
                                    'defaultOpen' => true,
                                ],
                                [
                                    'id' => 'tamper-evident',
                                    'question' => 'Are products sealed with tamper-evident tape?',
                                    'answer' => 'Yes. Inner packs use tamper-evident seals where required. If a seal looks broken on arrival, refuse the handover and contact support for a DOA review.',
                                ],
                            ],
                        ],
                        [
                            'id' => 'security',
                            'title' => 'Security & OTP Handover',
                            'icon' => 'shield-check',
                            'items' => [
                                [
                                    'id' => 'otp-handover',
                                    'question' => 'Why is OTP verification required at handover?',
                                    'answer' => 'A one-time code confirms you (or your authorised adult recipient) are present. It protects age-restricted goods and reduces misdelivery or third-party interception.',
                                ],
                                [
                                    'id' => 'age-docs',
                                    'question' => 'What age verification documents are accepted?',
                                    'answer' => 'Valid government photo ID such as a driver licence, passport, or proof-of-age card. The recipient must be 18+; orders may be refused if ID cannot be verified.',
                                ],
                            ],
                        ],
                        [
                            'id' => 'warranty',
                            'title' => 'Warranty, Returns & Replacements',
                            'icon' => 'wrench',
                            'items' => [
                                [
                                    'id' => 'doa-policy',
                                    'question' => 'What is your Dead-on-Arrival (DOA) replacement policy?',
                                    'answer' => 'Report DOA issues within 24 hours of delivery with photos of packaging and the product. Eligible DOA items are replaced from the fulfilling branch or refunded when stock cannot be sourced.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'wholesale' => [
                'title' => 'Wholesale',
                'meta_title' => 'Wholesale Partner Program',
                'meta_description' => 'We supply businesses across Australia with authentic vapes and cigarettes, helping you keep your shelves stocked and your customers happy.',
                'is_published' => true,
                'payload' => [
                    'intro' => [
                        'title' => 'Wholesale Partner Program',
                        'body' => 'We supply businesses across Australia with authentic vapes and cigarettes, helping you keep your shelves stocked and your customers happy. Partner with us and experience wholesale made simple, fast, and reliable.',
                        'benefits' => [
                            [
                                'title' => 'Competitive Bulk Pricing',
                                'description' => 'Get prices designed to maximize your margins without compromising quality.',
                            ],
                            [
                                'title' => 'Fast, Australia-Wide Delivery',
                                'description' => 'No matter where you are, your orders are prioritized, tracked, and delivered on time.',
                            ],
                            [
                                'title' => 'Large Stock Availability',
                                'description' => 'From best-selling flavours to the latest products, we keep our warehouse ready so you never run out.',
                            ],
                            [
                                'title' => 'Personalised Support',
                                'description' => 'Our dedicated team is here to guide you, answer questions, and keep your business stocked smoothly.',
                            ],
                        ],
                    ],
                    'inquiry' => [
                        'title' => 'Start Your Wholesale Journey Today',
                        'subtitle' => 'Contact us and see how easy it is to keep your business thriving with vapes and cigarettes.',
                    ],
                    'form' => [
                        'firstName' => 'First Name',
                        'lastName' => 'Last Name',
                        'phone' => 'Phone',
                        'email' => 'Email',
                        'company' => 'Company (If Applicable)',
                        'askQuestion' => 'Ask Question',
                        'message' => 'Message',
                        'firstNamePlaceholder' => 'John',
                        'lastNamePlaceholder' => 'Doe',
                        'phonePlaceholder' => '+1 012 3456 789',
                        'emailPlaceholder' => 'demo@gmail.com',
                        'companyPlaceholder' => 'Company name',
                        'askQuestionPlaceholder' => 'What would you like to know?',
                        'messagePlaceholder' => 'Write your message..',
                        'submitLabel' => 'Send Message',
                    ],
                ],
            ],
            default => throw new \InvalidArgumentException("Unknown CMS page slug [{$slug}]."),
        };
    }

    /**
     * @return array<string, array{title: string, meta_title: string|null, meta_description: string|null, is_published: bool, payload: array<string, mixed>}>
     */
    public static function all(): array
    {
        $all = [];

        foreach (self::slugs() as $slug) {
            $all[$slug] = self::definition($slug);
        }

        return $all;
    }
}
