export function paymentMethodLabel(method: string | null): string {
    switch (method) {
        case null:
        case '':
            return 'Not set';
        case 'cod':
            return 'Cash on delivery';
        case 'pos':
            return 'Card / POS';
        default:
            return method
                .replaceAll('_', ' ')
                .replace(/^\w/, (letter) => letter.toUpperCase());
    }
}
