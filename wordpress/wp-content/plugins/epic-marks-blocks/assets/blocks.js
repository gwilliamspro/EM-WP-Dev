(function(blocks, element, blockEditor, components, i18n, data) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = blockEditor.InspectorControls;
    var BlockControls = blockEditor.BlockControls;
    var PanelBody = components.PanelBody;
    var RangeControl = components.RangeControl;
    var ToggleControl = components.ToggleControl;
    var ColorPicker = components.ColorPicker;
    var ToolbarGroup = components.ToolbarGroup;
    var ToolbarButton = components.ToolbarButton;
    var __ = i18n.__;

    registerBlockType('epic-marks/wave-block', {
        title: __('Epic Marks Wave', 'epic-marks'),
        icon: 'ocean',
        category: 'design',
        attributes: {
            waveHeight: {
                type: 'number',
                default: 80
            },
            waveHeightMobile: {
                type: 'number',
                default: 60
            },
            waveColor: {
                type: 'string',
                default: '#627a94'
            },
            animate: {
                type: 'boolean',
                default: true
            },
            rotate: {
                type: 'boolean',
                default: false
            },
            backgroundColor: {
                type: 'string',
                default: 'transparent'
            },
            bottomSectionHeight: {
                type: 'number',
                default: 100
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            function onChangeWaveHeight(newValue) {
                setAttributes({ waveHeight: parseInt(newValue) });
            }

            function onChangeWaveHeightMobile(newValue) {
                setAttributes({ waveHeightMobile: parseInt(newValue) });
            }

            function onChangeBottomSectionHeight(newValue) {
                setAttributes({ bottomSectionHeight: parseInt(newValue) });
            }

            function onChangeWaveColor(newValue) {
                setAttributes({ waveColor: newValue.hex });
            }

            function onChangeBackgroundColor(newValue) {
                setAttributes({ backgroundColor: newValue.hex });
            }

            function onChangeAnimate(newValue) {
                setAttributes({ animate: newValue });
            }

            function onChangeRotate(newValue) {
                setAttributes({ rotate: newValue });
            }

            return el(
                'div',
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: __('Wave Settings', 'epic-marks'), initialOpen: true },
                        el(RangeControl, {
                            label: __('Wave Height (Desktop)', 'epic-marks'),
                            value: attributes.waveHeight,
                            onChange: onChangeWaveHeight,
                            min: 20,
                            max: 200,
                            step: 10
                        }),
                        el(RangeControl, {
                            label: __('Wave Height (Mobile)', 'epic-marks'),
                            value: attributes.waveHeightMobile,
                            onChange: onChangeWaveHeightMobile,
                            min: 20,
                            max: 200,
                            step: 10
                        }),
                        el(RangeControl, {
                            label: __('Bottom Section Height (Countdown Area)', 'epic-marks'),
                            value: attributes.bottomSectionHeight,
                            onChange: onChangeBottomSectionHeight,
                            min: 0,
                            max: 300,
                            step: 10,
                            help: __('Height of the solid colored section below the waves', 'epic-marks')
                        }),
                        el(ToggleControl, {
                            label: __('Animate Waves', 'epic-marks'),
                            checked: attributes.animate,
                            onChange: onChangeAnimate
                        }),
                        el(ToggleControl, {
                            label: __('Rotate Waves (Flip Upside Down)', 'epic-marks'),
                            checked: attributes.rotate,
                            onChange: onChangeRotate
                        })
                    ),
                    el(
                        PanelBody,
                        { title: __('Colors', 'epic-marks'), initialOpen: false },
                        el('p', { style: { marginBottom: '8px', fontWeight: '600' } }, __('Wave Color', 'epic-marks')),
                        el(ColorPicker, {
                            color: attributes.waveColor,
                            onChangeComplete: onChangeWaveColor,
                            disableAlpha: false
                        }),
                        el('p', { style: { marginTop: '16px', marginBottom: '8px', fontWeight: '600' } }, __('Background Color', 'epic-marks')),
                        el(ColorPicker, {
                            color: attributes.backgroundColor,
                            onChangeComplete: onChangeBackgroundColor,
                            disableAlpha: false
                        })
                    )
                ),
                el(
                    'div',
                    {
                        className: 'em-wave-block-preview',
                        style: {
                            background: attributes.backgroundColor,
                            padding: '20px',
                            border: '2px dashed #ccc',
                            borderRadius: '4px',
                            textAlign: 'center'
                        }
                    },
                    el('div', {
                        style: {
                            fontSize: '48px',
                            marginBottom: '10px'
                        }
                    }, '〰️'),
                    el('p', { style: { margin: 0, color: '#666' } }, __('Epic Marks Wave Block', 'epic-marks')),
                    el('p', { style: { margin: '8px 0 0 0', fontSize: '12px', color: '#999' } }, 
                        __('Wave: ', 'epic-marks') + attributes.waveHeight + 'px | ' +
                        __('Bottom: ', 'epic-marks') + attributes.bottomSectionHeight + 'px | ' +
                        (attributes.animate ? __('Animated', 'epic-marks') : __('Static', 'epic-marks')) +
                        (attributes.rotate ? ' | ' + __('Rotated', 'epic-marks') : '')
                    )
                )
            );
        },

        save: function() {
            // Dynamic block, rendered server-side
            return null;
        }
    });

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.i18n,
    window.wp.data
);

// ========================================
// COUNTDOWN BANNER BLOCK
// ========================================
(function(blocks, element, blockEditor, components, i18n) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var RangeControl = components.RangeControl;
    var TextControl = components.TextControl;
    var ColorPicker = components.ColorPicker;
    var __ = i18n.__;

    registerBlockType('epic-marks/countdown-block', {
        title: __('Epic Marks Countdown Banner', 'epic-marks'),
        icon: 'clock',
        category: 'widgets',
        attributes: {
            backgroundColor: {
                type: 'string',
                default: '#627a94'
            },
            textColor: {
                type: 'string',
                default: '#ffffff'
            },
            textSize: {
                type: 'number',
                default: 18
            },
            countdownText: {
                type: 'string',
                default: 'Order in 2h 30m to ship today (by 2:00 PM CT).'
            },
            paddingTop: {
                type: 'number',
                default: 12
            },
            paddingBottom: {
                type: 'number',
                default: 12
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            function onChangeBackgroundColor(newValue) {
                setAttributes({ backgroundColor: newValue.hex });
            }

            function onChangeTextColor(newValue) {
                setAttributes({ textColor: newValue.hex });
            }

            function onChangeTextSize(newValue) {
                setAttributes({ textSize: parseInt(newValue) });
            }

            function onChangeCountdownText(newValue) {
                setAttributes({ countdownText: newValue });
            }

            function onChangePaddingTop(newValue) {
                setAttributes({ paddingTop: parseInt(newValue) });
            }

            function onChangePaddingBottom(newValue) {
                setAttributes({ paddingBottom: parseInt(newValue) });
            }

            return el(
                'div',
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: __('Banner Settings', 'epic-marks'), initialOpen: true },
                        el(TextControl, {
                            label: __('Countdown Text', 'epic-marks'),
                            value: attributes.countdownText,
                            onChange: onChangeCountdownText,
                            help: __('Static text for now. Live countdown logic can be added later.', 'epic-marks')
                        }),
                        el(RangeControl, {
                            label: __('Text Size (px)', 'epic-marks'),
                            value: attributes.textSize,
                            onChange: onChangeTextSize,
                            min: 12,
                            max: 32,
                            step: 1
                        }),
                        el(RangeControl, {
                            label: __('Padding Top (px)', 'epic-marks'),
                            value: attributes.paddingTop,
                            onChange: onChangePaddingTop,
                            min: 0,
                            max: 50,
                            step: 2
                        }),
                        el(RangeControl, {
                            label: __('Padding Bottom (px)', 'epic-marks'),
                            value: attributes.paddingBottom,
                            onChange: onChangePaddingBottom,
                            min: 0,
                            max: 50,
                            step: 2
                        })
                    ),
                    el(
                        PanelBody,
                        { title: __('Colors', 'epic-marks'), initialOpen: false },
                        el('p', { style: { marginBottom: '8px', fontWeight: '600' } }, __('Background Color', 'epic-marks')),
                        el(ColorPicker, {
                            color: attributes.backgroundColor,
                            onChangeComplete: onChangeBackgroundColor,
                            disableAlpha: false
                        }),
                        el('p', { style: { marginTop: '16px', marginBottom: '8px', fontWeight: '600' } }, __('Text Color', 'epic-marks')),
                        el(ColorPicker, {
                            color: attributes.textColor,
                            onChangeComplete: onChangeTextColor,
                            disableAlpha: false
                        })
                    )
                ),
                el(
                    'div',
                    {
                        className: 'em-countdown-preview',
                        style: {
                            background: attributes.backgroundColor,
                            color: attributes.textColor,
                            padding: attributes.paddingTop + 'px 1rem ' + attributes.paddingBottom + 'px 1rem',
                            textAlign: 'center',
                            fontSize: attributes.textSize + 'px',
                            fontWeight: '700',
                            fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                            lineHeight: '1.4',
                            border: '2px dashed rgba(255,255,255,0.3)',
                            borderRadius: '4px'
                        }
                    },
                    attributes.countdownText
                )
            );
        },

        save: function() {
            // Dynamic block, rendered server-side
            return null;
        }
    });

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.i18n
);

// ========================================
// USP ITEM BLOCK WITH COPY/PASTE STYLES
// ========================================
(function(blocks, element, blockEditor, components, i18n) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = blockEditor.InspectorControls;
    var BlockControls = blockEditor.BlockControls;
    var PanelBody = components.PanelBody;
    var RangeControl = components.RangeControl;
    var TextControl = components.TextControl;
    var ColorPalette = components.ColorPalette;
    var ToolbarGroup = components.ToolbarGroup;
    var ToolbarButton = components.ToolbarButton;
    var __ = i18n.__;

    // Epic Marks brand color palette
    var emColors = [
        { name: 'Slate Gray', color: '#454C57' },
        { name: 'Steel Blue', color: '#627A94' },
        { name: 'Accent Hover', color: '#C2CCD1' },
        { name: 'White', color: '#FFFFFF' },
        { name: 'Neutral BG', color: '#F2F7F9' },
        { name: 'Success', color: '#64BF99' },
        { name: 'Error', color: '#DA3F3F' },
        { name: 'Border', color: '#E6EEF2' },
        { name: 'Transparent', color: 'transparent' }
    ];

    // Clipboard for style copying
    var styleClipboard = null;

    registerBlockType('epic-marks/usp-block', {
        title: __('Epic Marks USP', 'epic-marks'),
        icon: 'star-filled',
        category: 'design',
        attributes: {
            icon: {
                type: 'string',
                default: '⭐'
            },
            title: {
                type: 'string',
                default: 'FEATURE TITLE'
            },
            iconSize: {
                type: 'number',
                default: 32
            },
            titleSize: {
                type: 'number',
                default: 16
            },
            textColor: {
                type: 'string',
                default: '#454C57'
            },
            backgroundColor: {
                type: 'string',
                default: 'transparent'
            },
            borderRadius: {
                type: 'number',
                default: 0
            },
            boxShadow: {
                type: 'boolean',
                default: false
            },
            padding: {
                type: 'number',
                default: 16
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            function onChangeIcon(newValue) {
                setAttributes({ icon: newValue });
            }

            function onChangeTitle(newValue) {
                setAttributes({ title: newValue });
            }

            function onChangeIconSize(newValue) {
                setAttributes({ iconSize: parseInt(newValue) });
            }

            function onChangeTitleSize(newValue) {
                setAttributes({ titleSize: parseInt(newValue) });
            }

            function onChangeTextColor(newValue) {
                setAttributes({ textColor: newValue });
            }

            function onChangeBackgroundColor(newValue) {
                setAttributes({ backgroundColor: newValue });
            }

            function onChangeBorderRadius(newValue) {
                setAttributes({ borderRadius: parseInt(newValue) });
            }

            function onChangeBoxShadow(newValue) {
                setAttributes({ boxShadow: newValue });
            }

            function onChangePadding(newValue) {
                setAttributes({ padding: parseInt(newValue) });
            }

            function copyStyles() {
                styleClipboard = {
                    iconSize: attributes.iconSize,
                    titleSize: attributes.titleSize,
                    textColor: attributes.textColor,
                    backgroundColor: attributes.backgroundColor,
                    borderRadius: attributes.borderRadius,
                    boxShadow: attributes.boxShadow,
                    padding: attributes.padding
                };
                
                // Show notification
                wp.data.dispatch('core/notices').createNotice(
                    'success',
                    __('Styles copied!', 'epic-marks'),
                    { type: 'snackbar', isDismissible: true }
                );
            }

            function pasteStyles() {
                if (styleClipboard) {
                    setAttributes(styleClipboard);
                    
                    // Show notification
                    wp.data.dispatch('core/notices').createNotice(
                        'success',
                        __('Styles pasted!', 'epic-marks'),
                        { type: 'snackbar', isDismissible: true }
                    );
                }
            }

            return el(
                'div',
                {},
                el(
                    BlockControls,
                    {},
                    el(
                        ToolbarGroup,
                        {},
                        el(ToolbarButton, {
                            icon: 'admin-page',
                            label: __('Copy Styles', 'epic-marks'),
                            onClick: copyStyles
                        }),
                        el(ToolbarButton, {
                            icon: 'clipboard',
                            label: __('Paste Styles', 'epic-marks'),
                            onClick: pasteStyles,
                            disabled: !styleClipboard
                        })
                    )
                ),
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: __('Content', 'epic-marks'), initialOpen: true },
                        el(TextControl, {
                            label: __('Icon/Emoji', 'epic-marks'),
                            value: attributes.icon,
                            onChange: onChangeIcon,
                            help: __('Use an emoji or text icon (leave blank for text-only)', 'epic-marks')
                        }),
                        el(TextControl, {
                            label: __('Title', 'epic-marks'),
                            value: attributes.title,
                            onChange: onChangeTitle
                        }),
                        el(RangeControl, {
                            label: __('Icon Size (px)', 'epic-marks'),
                            value: attributes.iconSize,
                            onChange: onChangeIconSize,
                            min: 16,
                            max: 64,
                            step: 2
                        }),
                        el(RangeControl, {
                            label: __('Title Size (px)', 'epic-marks'),
                            value: attributes.titleSize,
                            onChange: onChangeTitleSize,
                            min: 12,
                            max: 24,
                            step: 1
                        })
                    ),
                    el(
                        PanelBody,
                        { title: __('Colors', 'epic-marks'), initialOpen: false },
                        el('p', { style: { marginBottom: '8px', fontWeight: '600' } }, __('Text Color', 'epic-marks')),
                        el(ColorPalette, {
                            colors: emColors,
                            value: attributes.textColor,
                            onChange: onChangeTextColor
                        }),
                        el('p', { style: { marginTop: '16px', marginBottom: '8px', fontWeight: '600' } }, __('Background Color', 'epic-marks')),
                        el(ColorPalette, {
                            colors: emColors,
                            value: attributes.backgroundColor,
                            onChange: onChangeBackgroundColor
                        })
                    ),
                    el(
                        PanelBody,
                        { title: __('Design', 'epic-marks'), initialOpen: false },
                        el(RangeControl, {
                            label: __('Padding (px)', 'epic-marks'),
                            value: attributes.padding,
                            onChange: onChangePadding,
                            min: 0,
                            max: 48,
                            step: 4
                        }),
                        el(RangeControl, {
                            label: __('Border Radius (px)', 'epic-marks'),
                            value: attributes.borderRadius,
                            onChange: onChangeBorderRadius,
                            min: 0,
                            max: 32,
                            step: 2
                        }),
                        el(components.ToggleControl, {
                            label: __('Drop Shadow', 'epic-marks'),
                            checked: attributes.boxShadow,
                            onChange: onChangeBoxShadow
                        })
                    )
                ),
                el(
                    'div',
                    {
                        className: 'em-usp-preview',
                        style: {
                            textAlign: 'center',
                            color: attributes.textColor,
                            background: attributes.backgroundColor,
                            padding: attributes.padding + 'px',
                            borderRadius: attributes.borderRadius + 'px',
                            boxShadow: attributes.boxShadow ? '0 2px 6px rgba(0,0,0,0.08)' : 'none',
                            border: '2px dashed #ccc'
                        }
                    },
                    attributes.icon ? el('div', {
                        style: {
                            fontSize: attributes.iconSize + 'px',
                            marginBottom: '8px',
                            lineHeight: '1'
                        }
                    }, attributes.icon) : null,
                    el('div', {
                        style: {
                            fontSize: attributes.titleSize + 'px',
                            fontWeight: '600',
                            textTransform: 'uppercase',
                            fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
                        }
                    }, attributes.title)
                )
            );
        },

        save: function() {
            // Dynamic block, rendered server-side
            return null;
        }
    });

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.i18n
);

// ========================================
// SERVICE SHOWCASE BLOCK
// ========================================
(function(blocks, element, blockEditor, components, i18n) {
    var el = element.createElement;
    var Fragment = element.Fragment;
    var registerBlockType = blocks.registerBlockType;
    var InspectorControls = blockEditor.InspectorControls;
    var MediaUpload = blockEditor.MediaUpload;
    var PanelBody = components.PanelBody;
    var RangeControl = components.RangeControl;
    var TextControl = components.TextControl;
    var TextareaControl = components.TextareaControl;
    var SelectControl = components.SelectControl;
    var ToggleControl = components.ToggleControl;
    var ColorPalette = components.ColorPalette;
    var Button = components.Button;
    var __ = i18n.__;

    // Epic Marks brand color palette
    var emColors = [
        { name: 'Slate Gray', color: '#454C57' },
        { name: 'Steel Blue', color: '#627A94' },
        { name: 'Accent Hover', color: '#C2CCD1' },
        { name: 'White', color: '#FFFFFF' },
        { name: 'Neutral BG', color: '#F2F7F9' }
    ];

    registerBlockType('epic-marks/showcase-block', {
        title: __('Service Showcase', 'epic-marks'),
        icon: 'grid-view',
        category: 'layout',
        attributes: {
            title: {
                type: 'string',
                default: 'Shop Sublimation'
            },
            hideTitle: {
                type: 'boolean',
                default: false
            },
            columns: {
                type: 'number',
                default: 3
            },
            imageAspectDesktop: {
                type: 'string',
                default: '1/1'
            },
            imageAspectMobile: {
                type: 'string',
                default: '1/1'
            },
            imageFit: {
                type: 'string',
                default: 'cover'
            },
            headingMaxLines: {
                type: 'number',
                default: 2
            },
            textMaxLines: {
                type: 'number',
                default: 0
            },
            cards: {
                type: 'array',
                default: []
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            function addCard() {
                var newCards = attributes.cards.slice();
                newCards.push({
                    heading: 'New Service',
                    description: 'Service description here...',
                    image: {},
                    hoverImage: {},
                    showNewBadge: false,
                    newBadgeLabel: 'NEW',
                    newBadgeColor: '#454C57',
                    priceList: [],
                    showSizeRange: true,
                    showFromBadge: true,
                    fromBadgeColor: '#627A94',
                    openSizes: false,
                    ctaLabel: 'Learn More',
                    ctaLink: ''
                });
                setAttributes({ cards: newCards });
            }

            function removeCard(index) {
                var newCards = attributes.cards.slice();
                newCards.splice(index, 1);
                setAttributes({ cards: newCards });
            }

            function updateCard(index, field, value) {
                var newCards = attributes.cards.slice();
                newCards[index][field] = value;
                setAttributes({ cards: newCards });
            }

            function addPriceRow(cardIndex) {
                var newCards = attributes.cards.slice();
                if (!newCards[cardIndex].priceList) {
                    newCards[cardIndex].priceList = [];
                }
                newCards[cardIndex].priceList.push({ size: '', price: 0 });
                setAttributes({ cards: newCards });
            }

            function updatePriceRow(cardIndex, rowIndex, field, value) {
                var newCards = attributes.cards.slice();
                newCards[cardIndex].priceList[rowIndex][field] = value;
                setAttributes({ cards: newCards });
            }

            function removePriceRow(cardIndex, rowIndex) {
                var newCards = attributes.cards.slice();
                newCards[cardIndex].priceList.splice(rowIndex, 1);
                setAttributes({ cards: newCards });
            }

            function moveCardUp(index) {
                if (index === 0) return;
                var newCards = attributes.cards.slice();
                var temp = newCards[index];
                newCards[index] = newCards[index - 1];
                newCards[index - 1] = temp;
                setAttributes({ cards: newCards });
            }

            function moveCardDown(index) {
                if (index === attributes.cards.length - 1) return;
                var newCards = attributes.cards.slice();
                var temp = newCards[index];
                newCards[index] = newCards[index + 1];
                newCards[index + 1] = temp;
                setAttributes({ cards: newCards });
            }

            return el(
                Fragment,
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: __('Showcase Settings', 'epic-marks'), initialOpen: true },
                        el(TextControl, {
                            label: __('Section Title', 'epic-marks'),
                            value: attributes.title,
                            onChange: function(value) { setAttributes({ title: value }); }
                        }),
                        el(ToggleControl, {
                            label: __('Hide Title', 'epic-marks'),
                            checked: attributes.hideTitle,
                            onChange: function(value) { setAttributes({ hideTitle: value }); }
                        }),
                        el(RangeControl, {
                            label: __('Columns', 'epic-marks'),
                            value: attributes.columns,
                            onChange: function(value) { setAttributes({ columns: value }); },
                            min: 2,
                            max: 4,
                            step: 1
                        }),
                        el(SelectControl, {
                            label: __('Image Aspect (Desktop)', 'epic-marks'),
                            value: attributes.imageAspectDesktop,
                            options: [
                                { label: '1:1 Square', value: '1/1' },
                                { label: '4:3', value: '4/3' },
                                { label: '3:2', value: '3/2' },
                                { label: '16:9', value: '16/9' }
                            ],
                            onChange: function(value) { setAttributes({ imageAspectDesktop: value }); }
                        }),
                        el(SelectControl, {
                            label: __('Image Aspect (Mobile)', 'epic-marks'),
                            value: attributes.imageAspectMobile,
                            options: [
                                { label: '1:1 Square', value: '1/1' },
                                { label: '4:3', value: '4/3' },
                                { label: '3:2', value: '3/2' },
                                { label: '16:9', value: '16/9' }
                            ],
                            onChange: function(value) { setAttributes({ imageAspectMobile: value }); }
                        }),
                        el(SelectControl, {
                            label: __('Image Fit', 'epic-marks'),
                            value: attributes.imageFit,
                            options: [
                                { label: 'Cover (fill)', value: 'cover' },
                                { label: 'Contain (fit)', value: 'contain' }
                            ],
                            onChange: function(value) { setAttributes({ imageFit: value }); }
                        }),
                        el(RangeControl, {
                            label: __('Heading Max Lines', 'epic-marks'),
                            value: attributes.headingMaxLines,
                            onChange: function(value) { setAttributes({ headingMaxLines: value }); },
                            min: 1,
                            max: 3,
                            step: 1
                        }),
                        el(RangeControl, {
                            label: __('Description Max Lines (0 = unlimited)', 'epic-marks'),
                            value: attributes.textMaxLines,
                            onChange: function(value) { setAttributes({ textMaxLines: value }); },
                            min: 0,
                            max: 12,
                            step: 1
                        })
                    )
                ),
                el(
                    'div',
                    {
                        className: 'em-showcase-editor',
                        style: { border: '2px dashed #ccc', padding: '20px', borderRadius: '4px' }
                    },
                    el('div', { style: { textAlign: 'center', marginBottom: '20px' } },
                        !attributes.hideTitle ? el('h3', { style: { margin: '0 0 10px 0', color: '#1e1e1e' } }, attributes.title || 'Service Showcase') : null,
                        el('p', { style: { margin: '0 0 10px 0', fontSize: '12px', color: '#666' } },
                            attributes.cards.length + ' cards | ' + attributes.columns + ' columns' + (attributes.hideTitle ? ' | Title Hidden' : '')
                        ),
                        el(Button, {
                            isPrimary: true,
                            onClick: addCard
                        }, __('Add Card', 'epic-marks'))
                    ),
                    attributes.cards.map(function(card, cardIndex) {
                        return el(
                            'div',
                            {
                                key: cardIndex,
                                style: {
                                    border: '1px solid #ddd',
                                    padding: '15px',
                                    marginBottom: '15px',
                                    borderRadius: '4px',
                                    background: '#f9f9f9'
                                }
                            },
                            el('div', { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '10px' } },
                                el('h4', { style: { margin: 0, color: '#1e1e1e' } }, __('Card ', 'epic-marks') + (cardIndex + 1)),
                                el('div', { style: { display: 'flex', gap: '5px' } },
                                    el(Button, {
                                        isSmall: true,
                                        onClick: function() { moveCardUp(cardIndex); },
                                        disabled: cardIndex === 0,
                                        icon: 'arrow-up-alt2',
                                        label: __('Move Up', 'epic-marks')
                                    }),
                                    el(Button, {
                                        isSmall: true,
                                        onClick: function() { moveCardDown(cardIndex); },
                                        disabled: cardIndex === attributes.cards.length - 1,
                                        icon: 'arrow-down-alt2',
                                        label: __('Move Down', 'epic-marks')
                                    }),
                                    el(Button, {
                                        isDestructive: true,
                                        isSmall: true,
                                        onClick: function() { removeCard(cardIndex); }
                                    }, __('Remove', 'epic-marks'))
                                )
                            ),
                            el(TextControl, {
                                label: __('Heading', 'epic-marks'),
                                value: card.heading,
                                onChange: function(value) { updateCard(cardIndex, 'heading', value); }
                            }),
                            el(TextareaControl, {
                                label: __('Description', 'epic-marks'),
                                value: card.description,
                                onChange: function(value) { updateCard(cardIndex, 'description', value); },
                                rows: 3
                            }),
                            el('div', { style: { marginBottom: '10px' } },
                                el('p', { style: { fontWeight: '600', marginBottom: '5px', color: '#1e1e1e' } }, __('Main Image', 'epic-marks')),
                                el(MediaUpload, {
                                    onSelect: function(media) {
                                        updateCard(cardIndex, 'image', {
                                            id: media.id,
                                            url: media.url,
                                            alt: media.alt,
                                            width: media.width,
                                            height: media.height
                                        });
                                    },
                                    type: 'image',
                                    value: card.image ? card.image.id : null,
                                    render: function(obj) {
                                        return el(Button, {
                                            onClick: obj.open,
                                            isSecondary: true
                                        }, card.image && card.image.url ? __('Change Image', 'epic-marks') : __('Select Image', 'epic-marks'));
                                    }
                                }),
                                card.image && card.image.url ? el('img', {
                                    src: card.image.url,
                                    style: { maxWidth: '100px', marginTop: '10px', display: 'block' }
                                }) : null
                            ),
                            el('div', { style: { marginBottom: '10px' } },
                                el('p', { style: { fontWeight: '600', marginBottom: '5px', color: '#1e1e1e' } }, __('Hover Image (optional)', 'epic-marks')),
                                el(MediaUpload, {
                                    onSelect: function(media) {
                                        updateCard(cardIndex, 'hoverImage', {
                                            id: media.id,
                                            url: media.url,
                                            alt: media.alt,
                                            width: media.width,
                                            height: media.height
                                        });
                                    },
                                    type: 'image',
                                    value: card.hoverImage ? card.hoverImage.id : null,
                                    render: function(obj) {
                                        return el(Button, {
                                            onClick: obj.open,
                                            isSecondary: true
                                        }, card.hoverImage && card.hoverImage.url ? __('Change Hover Image', 'epic-marks') : __('Select Hover Image', 'epic-marks'));
                                    }
                                })
                            ),
                            el(ToggleControl, {
                                label: __('Show NEW Badge', 'epic-marks'),
                                checked: card.showNewBadge,
                                onChange: function(value) { updateCard(cardIndex, 'showNewBadge', value); }
                            }),
                            card.showNewBadge ? el(Fragment, {},
                                el(TextControl, {
                                    label: __('Badge Label', 'epic-marks'),
                                    value: card.newBadgeLabel,
                                    onChange: function(value) { updateCard(cardIndex, 'newBadgeLabel', value); }
                                }),
                                el('p', { style: { marginBottom: '5px', fontWeight: '600', color: '#1e1e1e' } }, __('Badge Color', 'epic-marks')),
                                el(ColorPalette, {
                                    colors: emColors,
                                    value: card.newBadgeColor,
                                    onChange: function(value) { updateCard(cardIndex, 'newBadgeColor', value); }
                                })
                            ) : null,
                            el(TextControl, {
                                label: __('CTA Label', 'epic-marks'),
                                value: card.ctaLabel,
                                onChange: function(value) { updateCard(cardIndex, 'ctaLabel', value); }
                            }),
                            el(TextControl, {
                                label: __('CTA Link', 'epic-marks'),
                                value: card.ctaLink,
                                onChange: function(value) { updateCard(cardIndex, 'ctaLink', value); }
                            }),
                            el('div', { style: { marginTop: '15px', paddingTop: '15px', borderTop: '1px solid #ddd' } },
                                el('p', { style: { fontWeight: '600', marginBottom: '10px', color: '#1e1e1e' } }, __('Pricing', 'epic-marks')),
                                el(ToggleControl, {
                                    label: __('Show Size Range', 'epic-marks'),
                                    checked: card.showSizeRange,
                                    onChange: function(value) { updateCard(cardIndex, 'showSizeRange', value); }
                                }),
                                el(ToggleControl, {
                                    label: __('Show "From" Badge', 'epic-marks'),
                                    checked: card.showFromBadge,
                                    onChange: function(value) { updateCard(cardIndex, 'showFromBadge', value); }
                                }),
                                el(ToggleControl, {
                                    label: __('Expand Sizes by Default', 'epic-marks'),
                                    checked: card.openSizes,
                                    onChange: function(value) { updateCard(cardIndex, 'openSizes', value); }
                                }),
                                el(Button, {
                                    isSecondary: true,
                                    isSmall: true,
                                    onClick: function() { addPriceRow(cardIndex); }
                                }, __('Add Price Row', 'epic-marks')),
                                card.priceList && card.priceList.length > 0 ? el('div', { style: { marginTop: '10px' } },
                                    card.priceList.map(function(priceRow, rowIndex) {
                                        return el('div', {
                                            key: rowIndex,
                                            style: { display: 'flex', gap: '10px', marginBottom: '5px', alignItems: 'flex-end' }
                                        },
                                            el('div', { style: { flex: 1 } },
                                                el(TextControl, {
                                                    label: rowIndex === 0 ? __('Size', 'epic-marks') : '',
                                                    value: priceRow.size,
                                                    onChange: function(value) { updatePriceRow(cardIndex, rowIndex, 'size', value); },
                                                    placeholder: '22x12'
                                                })
                                            ),
                                            el('div', { style: { flex: 1 } },
                                                el(TextControl, {
                                                    label: rowIndex === 0 ? __('Price', 'epic-marks') : '',
                                                    value: priceRow.price,
                                                    onChange: function(value) { updatePriceRow(cardIndex, rowIndex, 'price', parseFloat(value) || 0); },
                                                    type: 'number',
                                                    step: '0.01',
                                                    placeholder: '25.00'
                                                })
                                            ),
                                            el(Button, {
                                                isDestructive: true,
                                                isSmall: true,
                                                onClick: function() { removePriceRow(cardIndex, rowIndex); },
                                                style: { marginBottom: '3px' }
                                            }, 'X')
                                        );
                                    })
                                ) : null
                            )
                        );
                    })
                )
            );
        },

        save: function() {
            // Dynamic block, rendered server-side
            return null;
        }
    });

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.i18n
);
