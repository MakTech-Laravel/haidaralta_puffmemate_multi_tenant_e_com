import * as React from 'react';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Heading2,
    Heading3,
    ImageIcon,
    Italic,
    Link2,
    List,
    ListOrdered,
    Redo2,
    Undo2,
} from 'lucide-react';
import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type RichTextEditorProps = {
    value: string;
    onChange: (html: string) => void;
    placeholder?: string;
    className?: string;
    editable?: boolean;
};

function ToolbarButton({
    onClick,
    active,
    disabled,
    label,
    children,
}: {
    onClick: () => void;
    active?: boolean;
    disabled?: boolean;
    label: string;
    children: React.ReactNode;
}) {
    return (
        <Button
            type="button"
            variant={active ? 'secondary' : 'ghost'}
            size="icon-sm"
            aria-label={label}
            disabled={disabled}
            onClick={onClick}
        >
            {children}
        </Button>
    );
}

export function RichTextEditor({
    value,
    onChange,
    placeholder = 'Write content…',
    className,
    editable = true,
}: RichTextEditorProps) {
    const editor = useEditor({
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3] },
            }),
            Link.configure({
                openOnClick: false,
                HTMLAttributes: {
                    rel: 'noopener noreferrer',
                    target: '_blank',
                },
            }),
            Image.configure({
                allowBase64: false,
            }),
            Placeholder.configure({
                placeholder,
            }),
        ],
        content: value || '',
        editable,
        immediatelyRender: false,
        onUpdate: ({ editor: current }) => {
            onChange(current.getHTML());
        },
        editorProps: {
            attributes: {
                class: cn(
                    'cms-rich-text min-h-[180px] px-3 py-2 text-sm leading-relaxed focus:outline-none',
                    '[&_h2]:mt-4 [&_h2]:mb-2 [&_h2]:text-xl [&_h2]:font-semibold',
                    '[&_h3]:mt-3 [&_h3]:mb-1.5 [&_h3]:text-lg [&_h3]:font-semibold',
                    '[&_p]:mb-2 [&_ul]:mb-2 [&_ul]:list-disc [&_ul]:pl-5',
                    '[&_ol]:mb-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_a]:text-primary [&_a]:underline',
                    '[&_img]:my-2 [&_img]:max-w-full [&_img]:rounded-md',
                ),
            },
        },
    });

    useEffect(() => {
        if (!editor) {
            return;
        }

        const current = editor.getHTML();
        const next = value || '';

        if (current !== next) {
            editor.commands.setContent(next, { emitUpdate: false });
        }
    }, [editor, value]);

    if (!editor) {
        return null;
    }

    const setLink = () => {
        const previous = editor.getAttributes('link').href as string | undefined;
        const url = window.prompt('Link URL', previous ?? 'https://');

        if (url === null) {
            return;
        }

        if (url.trim() === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();

            return;
        }

        editor
            .chain()
            .focus()
            .extendMarkRange('link')
            .setLink({ href: url.trim() })
            .run();
    };

    const setImage = () => {
        const url = window.prompt('Image URL', 'https://');

        if (!url?.trim()) {
            return;
        }

        editor.chain().focus().setImage({ src: url.trim() }).run();
    };

    return (
        <div
            className={cn(
                'border-input bg-background overflow-hidden rounded-md border',
                className,
            )}
        >
            {editable ? (
                <div className="border-border/80 flex flex-wrap items-center gap-0.5 border-b p-1.5">
                    <ToolbarButton
                        label="Bold"
                        active={editor.isActive('bold')}
                        onClick={() =>
                            editor.chain().focus().toggleBold().run()
                        }
                    >
                        <Bold className="size-4" />
                    </ToolbarButton>
                    <ToolbarButton
                        label="Italic"
                        active={editor.isActive('italic')}
                        onClick={() =>
                            editor.chain().focus().toggleItalic().run()
                        }
                    >
                        <Italic className="size-4" />
                    </ToolbarButton>
                    <ToolbarButton
                        label="Heading 2"
                        active={editor.isActive('heading', { level: 2 })}
                        onClick={() =>
                            editor
                                .chain()
                                .focus()
                                .toggleHeading({ level: 2 })
                                .run()
                        }
                    >
                        <Heading2 className="size-4" />
                    </ToolbarButton>
                    <ToolbarButton
                        label="Heading 3"
                        active={editor.isActive('heading', { level: 3 })}
                        onClick={() =>
                            editor
                                .chain()
                                .focus()
                                .toggleHeading({ level: 3 })
                                .run()
                        }
                    >
                        <Heading3 className="size-4" />
                    </ToolbarButton>
                    <ToolbarButton
                        label="Bullet list"
                        active={editor.isActive('bulletList')}
                        onClick={() =>
                            editor.chain().focus().toggleBulletList().run()
                        }
                    >
                        <List className="size-4" />
                    </ToolbarButton>
                    <ToolbarButton
                        label="Ordered list"
                        active={editor.isActive('orderedList')}
                        onClick={() =>
                            editor.chain().focus().toggleOrderedList().run()
                        }
                    >
                        <ListOrdered className="size-4" />
                    </ToolbarButton>
                    <ToolbarButton label="Link" onClick={setLink}>
                        <Link2 className="size-4" />
                    </ToolbarButton>
                    <ToolbarButton label="Image" onClick={setImage}>
                        <ImageIcon className="size-4" />
                    </ToolbarButton>
                    <div className="bg-border mx-1 h-5 w-px" />
                    <ToolbarButton
                        label="Undo"
                        onClick={() => editor.chain().focus().undo().run()}
                        disabled={!editor.can().undo()}
                    >
                        <Undo2 className="size-4" />
                    </ToolbarButton>
                    <ToolbarButton
                        label="Redo"
                        onClick={() => editor.chain().focus().redo().run()}
                        disabled={!editor.can().redo()}
                    >
                        <Redo2 className="size-4" />
                    </ToolbarButton>
                </div>
            ) : null}
            <EditorContent editor={editor} />
        </div>
    );
}
