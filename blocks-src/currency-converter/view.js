/**
 * Use this file for JavaScript code that you want to run in the front-end
 * on posts/pages that contain this block.
 *
 * When this file is defined as the value of the `viewScript` property
 * in `block.json` it will be enqueued on the front end of the site.
 *
 * Example:
 *
 * ```js
 * {
 *   "viewScript": "file:./view.js"
 * }
 * ```
 *
 * If you're not making any changes to this file because your project doesn't need any
 * JavaScript running in the front-end, then you should delete this file and remove
 * the `viewScript` property from `block.json`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#view-script
 */

import { useState } from '@wordpress/element';
import { createRoot } from 'react-dom/client';


const CurrencyConverter = () => {

    const [conversionRates, setConversionRates] = useState(window.conversionData || {});

    const updateRates = (currency, value) => {
        const newRates = {};
        const basedCurrencyRate = window.conversionData[currency];
        if(value > 1000000000000) {
            value = value / 10;
        }
        Object.keys(window.conversionData).forEach((key) => {
            if (currency == key) {
                newRates[key] = value;
            } else {
                newRates[key] = ( value * parseFloat(window.conversionData[key]) / parseFloat(basedCurrencyRate) ).toFixed(2);
            }
        });
        
        setConversionRates(newRates);
    };

    const handleAmountChange = (currency, value) => {
        updateRates(currency, value);
     };

    return (
        <>
            {Object.keys(conversionRates).map((key) => (
                <div key={key}>
                    <label htmlFor={`currency-${key}`}>{key}</label>
                    <input
                        type="text"
                        id={`currency-${key}`}
                        value={conversionRates[key]}
                        onChange={(e) =>
                            handleAmountChange(key, parseFloat(e.target.value) || 0)
                        }
                    />
                </div>
            ))}
        </>
    );

};


document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('converter-rates');
    if (container && createRoot) {
        createRoot( container ).render( <CurrencyConverter /> );
    } else {
        console.log(window.conversionData);
        console.log(container);
        console.log(createRoot);
    }
});