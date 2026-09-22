import DOMPurify from 'isomorphic-dompurify';

const ALLOWED_TAGS = [
    'p',
    'br',
    'strong',
    'em',
    'u',
    's',
    'h1',
    'h2',
    'h3',
    'h4',
    'ul',
    'ol',
    'li',
    'a',
    'img',
    'blockquote',
    'code',
    'pre',
    'hr',
    'span',
];

const ALLOWED_ATTR = [
    'href',
    'target',
    'rel',
    'src',
    'alt',
    'title',
    'class',
    'width',
    'height',
];

export function sanitizeHtml(dirty: string | null | undefined): string {
    if (!dirty) {
        return '';
    }

    return DOMPurify.sanitize(dirty, {
        ALLOWED_TAGS,
        ALLOWED_ATTR,
        ALLOW_DATA_ATTR: false,
    });
}
