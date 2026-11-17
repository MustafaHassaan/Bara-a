jQuery(function ($) {
    const params = wc_aman_params;
    const name = 'kashier_contact';
    const label = window.wp.htmlEntities.decodeEntities(params.title) || window.wp.i18n.__('Kashier', 'kasheir_gateway');

    // Updated Icon component to handle multiple icons
    const Icon = () => {
        const icons = params.icons || [];

        if (icons.length === 0) return null;

        return window.wp.element.createElement(
            'div',
            { style: { display: 'flex', justifyContent: 'flex-start' } },
            icons.map((icon, index) =>
                window.wp.element.createElement('img', {
                    src: params.baseurl + 'assets/images/' + icon,
                    id: `kashier-icon-${index}`,
                    style: {
                        maxWidth: '70px',
                        marginLeft: '10px',
                        paddingTop: '6px',
                    },
                    alt: `Kashier Icon ${index + 1}`
                })
            )
        );
    };

    const LabelWithIcon = () => {
        return window.wp.element.createElement('div', { style: { width: '100%', display: 'flex', alignItems: 'center' } }, label, window.wp.element.createElement(Icon));
    };

    const Content = ({ testmode }) => {
        let updatedDescription = params.description;
        if (testmode) {
            updatedDescription += ' TEST MODE ENABLED';
        }

        return window.wp.element.createElement('div', { id: 'secured-by-kashier-container' },
            window.wp.element.createElement('p', null, updatedDescription),
            window.wp.element.createElement('img', {
                src: params.baseurl + 'assets/images/secured-by-kashier.png',
                alt: 'Secured by Kashier'
            })
        );
    };

    const Block_Gateway =  {
        name,
        label: window.wp.element.createElement(LabelWithIcon),
        content: window.wp.element.createElement(Content, null),
        edit: window.wp.element.createElement(Content, null),
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: params.supports,
        },
    };

    window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);
})
