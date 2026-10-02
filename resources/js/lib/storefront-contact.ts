export const contactHero = {
    title: 'Contact Us',
    subtitle:
        'Passionate about quality, design, and delivering value to customers',
    image: '/images/storefront/hero-1.jpg',
};

export const contactInfo = {
    title: 'Contact Information',
    subtitle: 'Say something to start a live chat!',
    phone: '+1 012 3456 789',
    email: 'demo@gmail.com',
    address: '132 Dartmouth Street Boston, Massachusetts 02156 United States',
    socials: [
        { id: 'twitter', label: 'Twitter', href: '#' },
        { id: 'instagram', label: 'Instagram', href: '#' },
        { id: 'discord', label: 'Discord', href: '#' },
    ] as const,
};

export const contactFormCopy = {
    fullName: 'Full Name',
    email: 'Email',
    phone: 'Phone Number',
    type: 'Type',
    message: 'Message',
    fullNamePlaceholder: 'Jane Citizen',
    phonePlaceholder: '+1 012 3456 789',
    typePlaceholder: 'Select a contact type',
    messagePlaceholder: 'Write your message..',
    submitLabel: 'Send Message',
};
