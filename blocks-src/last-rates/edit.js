/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */

import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { Panel, PanelBody, SelectControl, DateTimePicker  } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( props ) {

	const { attributes, setAttributes } = props;
    const { rateDate, selectedCurrencies } = attributes;
	const [ currencies, setCurrencies ] = useState([]);
    const [ isLoading, setIsLoading ] = useState(true);

	const handleSelectChange = (newValues) => {
        setAttributes({ selectedCurrencies: newValues });
    };

	useEffect(() => {
		wp.apiFetch({ path: '/archive-nbp/v1/currencies' }).then((data) => {
			if ( Object.keys( data ).length > 0 ) {
				setCurrencies( data );
			} else {
				setCurrencies( [] );
			}
			setIsLoading( false );
		});
	}, []);


	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<Panel>
					<PanelBody title = { __( 'Currencies List', 'archive-nbp') }>
						<SelectControl 
							multiple
							value = { selectedCurrencies }
							options = { isLoading ? [{ label: __( 'Loading...', 'archive-nbp') , value: '' }] : Object.keys(currencies).map((key) => ( {
								label: key
							} )) }
							onChange = { handleSelectChange } 
						/>
					</PanelBody>
					<PanelBody 
						title = { __( 'Rates Date', 'archive-nbp') }
						className={ 'rates-date-picker' }
					>
						<DateTimePicker
							currentDate={ rateDate }
							onChange={ ( newDate ) => setAttributes( { rateDate: newDate } ) }

						/>
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div className='currency-rates-admin'> 
				<p> { __( 'Archive NBP Currency Rates Block', 'archive-nbp' ) } </p>
				<p> { __( 'Chosen currencies - ' + selectedCurrencies, 'archive-nbp' ) } </p>
				<p> { __( 'Rates Date - ' + rateDate.split('T')[0], 'archive-nbp' ) } </p>
			</div>
		</div>
	);
}
