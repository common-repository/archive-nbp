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
    const { currency, date_begin, date_end } = attributes;
	const [ currencies, setCurrencies ] = useState([]);
    const [ isLoading, setIsLoading ] = useState(true);

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
					<PanelBody title = { __( 'Currency', 'archive-nbp') }>
						<SelectControl 
							value = { currency }
							options = { isLoading ? [{ label: __( 'Loading...', 'archive-nbp') , value: '' }] : Object.keys(currencies).map((key) => ( {
								label: key
							} )) }
							onChange = { (newCurrency) => setAttributes({ currency: newCurrency }) } 
						/>
					</PanelBody>
					<PanelBody 
						title = { __( 'Begin Date', 'archive-nbp') } 
						className={'start-date-picker'}
					>
						<DateTimePicker 
							currentDate={ date_begin }
							onChange={ ( newDate ) => setAttributes( { date_begin: newDate } ) }
						/>
					</PanelBody>
					<PanelBody 
						title = { __( 'End Date', 'archive-nbp') }
						className={ 'end-date-picker' }
					>
						<DateTimePicker
							currentDate={ date_end }
							onChange={ ( newDate ) => setAttributes( { date_end: newDate } ) }

						/>
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div className='currency-chart-admin'> 
				<p> { __( 'Archive NBP Currency Chart Block', 'archive-nbp' ) } </p>
				<p> { __( 'Chosen currency - ' + currency, 'archive-nbp' ) } </p>
				<p> { __( 'Date begin - ' + date_begin.split('T')[0], 'archive-nbp' ) } </p>
				<p> { __( 'Date end - ' + date_end.split('T')[0], 'archive-nbp' ) } </p>
			</div>
		</div>
	);
}
