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
