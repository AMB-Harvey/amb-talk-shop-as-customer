alert("xsxs");
const settings = window.wc.wcSettings.getSetting( 'shop_as_client_data', {} );

const label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'My Custom Gateway', 'shop_as_client' );
const Content = () => {
    return window.wp.htmlEntities.decodeEntities( settings.description || '' );
};
const Block_Gateway = {
    name: 'shop_as_client',
    label: label,
    content: Object( window.wp.element.createElement )( Content, null ),
    edit: Object( window.wp.element.createElement )( Content, null ),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Block_Gateway );