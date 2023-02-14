import {
	DateTimePicker,
	RadioControl,
	SelectControl,
} from '@wordpress/components';
import { store as coreStore, useEntityProp } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
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

		const { editEntityRecord } = useDispatch( 'core' );

		const { selectedTerms, terms, taxonomy, postType, postId } = useSelect(
			( select ) => {
				const { getTaxonomy, getEntityRecords } = select( coreStore );

				// Get the full alert level taxonomy object.
				const alertLevelTaxonomy = getTaxonomy( slug );

				// Get information about the current post.
				const {
					getEditedPostAttribute,
					getCurrentPostType,
					getCurrentPostId,
				} = select( 'core/editor' );

				return {
					selectedTerms: alertLevelTaxonomy
						? getEditedPostAttribute(
								alertLevelTaxonomy.rest_base
						  ).filter( ( val ) => {
								return Number( val );
						  } )
						: [],
					terms:
						getEntityRecords( 'taxonomy', slug, {
							per_page: -1,
							orderby: 'name',
							order: 'asc',
							_fields: 'id,name',
							context: 'view',
						} ) || [],
					taxonomy: alertLevelTaxonomy,
					postType: getCurrentPostType(),
					postId: getCurrentPostId(),
				};
			},
			[]
		);

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
			const data = {};

			if ( 0 === Number( termID ) ) {
				setMeta( {
					_hp_alert_has_expiration: false,
					_hp_alert_display_through: '',
				} );
			}

			data[ taxonomy.rest_base ] = [ parseInt( termID, 10 ) ];
			editEntityRecord( 'postType', postType, postId, data );
		};

		return (
			<>
				<SelectControl
					label={ __( 'Alert level' ) }
					multiple={ false }
					onChange={ onChange }
					options={ termsList }
					value={ selectedTerms }
				/>
				{ 0 < selectedTerms.length && (
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
									_hp_alert_display_through: '',
								} );
							} else {
								setMeta( {
									_hp_alert_has_expiration: true,
								} );
							}
						} }
					/>
				) }
				{ 0 < selectedTerms.length && hasExpiration && (
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
