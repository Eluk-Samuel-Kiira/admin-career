/**
 * Shared TinyMCE options for CV and cover letter editing.
 * Keep the font list, sizes and content_style in line with the PDF template.
 */
window.cvEditorOptions = function (target, onInit, opts = {}) {
    return {
        target,
        license_key: 'gpl',
        height: opts.height || 600,
        menubar: false,
        statusbar: true,
        branding: false,
        promotion: false,

        plugins: 'lists link table code wordcount fullscreen searchreplace paste autolink',

        toolbar_mode: 'wrap',
        toolbar: [
            'undo redo | fontfamily fontsize blocks | bold italic underline strikethrough forecolor backcolor removeformat',
            'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link | searchreplace | code fullscreen',
        ].join(' | '),

        block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3',

        font_family_formats:
            'Georgia=Georgia,"Times New Roman",serif;' +
            'Times New Roman=times new roman,times,serif;' +
            'Garamond=garamond,serif;' +
            'Cambria=cambria,georgia,serif;' +
            'Calibri=calibri,carlito,sans-serif;' +
            'Arial=arial,helvetica,sans-serif;' +
            'Verdana=verdana,geneva,sans-serif;' +
            'Tahoma=tahoma,geneva,sans-serif;' +
            'Trebuchet MS=trebuchet ms,sans-serif',

        fontsize_formats: '9pt 10pt 10.5pt 11pt 12pt 13pt 14pt 16pt 18pt 20pt 24pt',
        font_size_input_default_unit: 'pt',

        color_map: [
            '1a1a1a', 'Black',
            '1f3a5f', 'Navy',
            '2563eb', 'Blue',
            '0f766e', 'Teal',
            '15803d', 'Green',
            'b91c1c', 'Red',
            '6b7280', 'Grey',
        ],

        content_style: `
            html {
                background: #525659;
            }
            body {
                box-sizing: border-box;
                width: auto;
                min-height: 1123px;
                margin: 24px;
                padding: 40px 45px;
                background: #ffffff;
                box-shadow: 0 2px 12px rgba(0,0,0,.4);
                font-family: Georgia, "Times New Roman", serif;
                font-size: 11pt;
                line-height: 1.5;
                color: #1a1a1a;
            }
            h1 { font-size: 22pt; margin: 0 0 4pt; font-weight: 700; }
            h2 { font-size: 13pt; margin: 16pt 0 6pt; font-weight: 700;
                 border-bottom: 2pt solid #1a1a1a; padding-bottom: 3pt; }
            h3 { font-size: 11pt; margin: 0; font-weight: 700; }
            p  { margin: 0 0 6pt; }
            .cv-header { text-align: center; margin-bottom: 6pt; }
            .cv-header h1 { text-align: center; letter-spacing: .5pt; }
            .cv-titles, .cv-contact { text-align: center; margin: 0 0 2pt; }
            .entry { margin: 0 0 10pt; }
            .employer { margin: 0 0 4pt; font-style: italic; }
            .referee { margin: 0 0 6pt; }
            table.entry-line { width: 100%; border-collapse: collapse; margin: 8pt 0 2pt; }
            table.entry-line td { padding: 0; vertical-align: baseline; border: 0; }
            table.entry-line td.dates { width: 1%; white-space: nowrap; text-align: right; font-size: 10pt; }
            ul, ol { margin: 0.4em 0 0.6em 1.4em; padding: 0; }
            li { margin: 0.2em 0; }

            @media (max-width: 820px) {
                body {
                    width: auto;
                    min-height: 0;
                    margin: 12px;
                    padding: 24px 20px;
                }
            }
        `,

        // Styles and classes must survive TinyMCE's own cleaning
        valid_styles: {
            '*': 'font-family,font-size,font-weight,font-style,text-decoration,color,background-color,text-align,line-height,margin-top,margin-bottom,margin-left',
        },
        extended_valid_elements:
            'span[style],p[class|style],div[class|style],h1[style],h2[style],h3[style],' +
            'li[style],td[style|colspan|rowspan],th[style|colspan|rowspan],' +
            'table[style|class|width|border|cellpadding|cellspacing],a[href|target|rel]',
        valid_classes: 'cv-header,cv-titles,cv-contact,entry,entry-line,dates,employer,referee',
        invalid_elements: 'script,style,iframe,object,embed',

        // Keep formatting when pasting from Word, Gmail or another page
        paste_as_text: false,
        paste_data_images: false,
        paste_webkit_styles: 'font-family font-size font-weight font-style color text-decoration',

        setup(editor) {
            editor.on('init', () => onInit && onInit(editor));
        },
    };
};