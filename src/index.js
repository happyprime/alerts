import {
	DateTimePicker,
	RadioControl,
	SelectControl,
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
				label: __( 'Select level' ),
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
			_hp_alert_has_expiration: hasExpiration,
		} = meta;

		let displayThroughValue;

		if ( ! displayThrough ) {
			displayThroughValue = new Date();
		} else {
			// Prep the display through value for JS Date compatibility. Microseconds!
			displayThroughValue = new Date( displayThrough * 1000 );
		}

		// Update the post with the selected alert level.
		const onChange = ( termID ) => {
			if ( 0 === Number( termID ) ) {
				setMeta( {
					_hp_alert_has_expiration: false,
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
					label={ __( 'Alert level' ) }
					multiple={ false }
					onChange={ onChange }
					options={ termsList }
					value={ alertLevels }
				/>
				{ 0 < alertLevels.length && (
					<RadioControl
						label={ __( 'Alert expires' ) }
						selected={ hasExpiration ? 'yes' : 'no' }
						options={ [
							{
								label: 'Yes',
								value: 'yes',
							},
							{
								label: 'No',
								value: 'no',
							},
						] }
						onChange={ ( value ) => {
							if ( 'no' === value ) {
								setMeta( {
									_hp_alert_has_expiration: false,
									_hp_alert_display_through: 0,
								} );
							} else {
								setMeta( {
									_hp_alert_has_expiration: true,
								} );
							}
						} }
					/>
				) }
				{ 0 < alertLevels.length && hasExpiration && (
					<DateTimePicker
						currentDate={ displayThroughValue }
						onChange={ ( newDate ) => {
							// Convert to a unix timestamp before storing. Milliseconds!
							const storeDate =
								new Date( newDate ).getTime() / 1000;

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
