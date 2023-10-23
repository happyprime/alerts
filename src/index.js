import {
	DateTimePicker,
	RadioControl,
	SelectControl,
	TextareaControl,
} from '@wordpress/components';
import { store as coreStore, useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

const setAlertLevel = ( OriginalComponent ) => {
	return ( props ) => {
		const { slug } = props;

		// Only override the alert level interface.
		if ( 'alert_level' !== slug ) {
			return <OriginalComponent { ...props } />;
		}

		const { terms, postType } = useSelect( ( select ) => {
			const { getEntityRecords } = select( coreStore );

			// Get information about the current post.
			const { getCurrentPostType } = select( 'core/editor' );

			return {
				terms:
					getEntityRecords( 'taxonomy', slug, {
						per_page: -1,
						orderby: 'name',
						order: 'asc',
						_fields: 'id,name',
						context: 'view',
					} ) || [],
				postType: getCurrentPostType(),
			};
		}, [] );

		// Parse available terms into a structure expected by the Select interface.
		const termData = terms.map( ( term ) => {
			return {
				label: decodeEntities( term.name ),
				value: term.id,
			};
		} );

		const termsList = [
			{
				label: __( 'Select level', 'hp-alerts' ),
				value: 0,
			},
			...termData,
		];

		const [ alertLevels, setAlertLevels ] = useEntityProp(
			'postType',
			postType,
			slug
		);
		const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
		const {
			_hp_alert_display_through: displayThrough,
			_hp_alert_title: title,
		} = meta;

		let displayThroughValue;

		// Prep the display through value for JS Date compatibility. Microseconds!
		if ( displayThrough ) {
			displayThroughValue = new Date( displayThrough * 1000 );
		}

		// Update the post with the selected alert level.
		const onChange = ( termID ) => {
			if ( 0 === Number( termID ) ) {
				setMeta( {
					_hp_alert_display_through: 0,
				} );
				setAlertLevels( [] );
			} else {
				setAlertLevels( [ parseInt( termID, 10 ) ] );
			}
		};

		return (
			<>
				<SelectControl
					label={ __( 'Alert level', 'hp-alerts' ) }
					multiple={ false }
					onChange={ onChange }
					options={ termsList }
					value={ alertLevels }
				/>
				{ 0 < alertLevels.length && (
					<>
						<TextareaControl
							help={ __(
								'Override the displayed title. The post title is displayed by default.',
								'hp-alerts'
							) }
							label={ __( 'Title (optional)', 'hp-alerts' ) }
							onChange={ ( value ) =>
								setMeta( {
									_hp_alert_title: value,
								} )
							}
							value={ title }
						/>
						<RadioControl
							label={ __( 'Alert expires', 'hp-alerts' ) }
							selected={ displayThrough ? 'yes' : 'no' }
							options={ [
								{
									label: __( 'Yes', 'hp-alerts' ),
									value: 'yes',
								},
								{
									label: __( 'No', 'hp-alerts' ),
									value: 'no',
								},
							] }
							onChange={ ( value ) => {
								if ( 'no' === value ) {
									setMeta( {
										_hp_alert_display_through: 0,
									} );
								} else {
									// Convert to a unix timestamp before storing. Milliseconds!
									const storeDate = Math.round(
										new Date().getTime() / 1000
									);

									setMeta( {
										_hp_alert_display_through: storeDate,
									} );
								}
							} }
						/>
					</>
				) }
				{ 0 < alertLevels.length && 0 !== displayThrough && (
					<DateTimePicker
						currentDate={ displayThroughValue }
						onChange={ ( newDate ) => {
							// Convert to a unix timestamp before storing. Milliseconds!
							const storeDate = Math.round(
								new Date( newDate ).getTime() / 1000
							);

							setMeta( { _hp_alert_display_through: storeDate } );
						} }
						is12Hour={ true }
						__nextRemoveHelpButton={ true }
						__nextRemoveResetButton={ true }
					/>
				) }
			</>
		);
	};
};

addFilter(
	'editor.PostTaxonomyType',
	'hp-alerts/set-alert-level',
	setAlertLevel
);
