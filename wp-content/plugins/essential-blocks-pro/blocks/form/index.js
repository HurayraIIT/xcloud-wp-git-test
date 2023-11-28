import { __ } from "@wordpress/i18n";
import { addFilter } from "@wordpress/hooks";
import { useEffect, useState, useRef } from "@wordpress/element";
import {
	TextControl,
	PanelRow,
	SelectControl
} from "@wordpress/components";


/**
 * Internal dependencies
 */
const {
	ProSelectControl
} = EBControls;

import MailchimpList from "./MailchimpList"


const FormIntegrations = (content, attributes, setAttributes) => {
	const {
		integrations
	} = attributes

	const integrationKeys = Object.keys(integrations)

	return (
		<>
			{integrationKeys.includes('mailchimp') && (
				<>
					<MailchimpList
						attributes={attributes}
						setAttributes={setAttributes}
					/>
				</>
			)}
		</>
	)
};
addFilter(
	"eb_form_block_control_after_form_type",
	"essential-blocks/pro",
	FormIntegrations,
	10,
	3
);


const confirmationTypeRedirect = (content, attributes, setAttributes) => {
	const {
		confirmationType,
		redirectUrl,
	} = attributes

	return (
		<>
			{confirmationType === 'redirect' && (
				<>
					<TextControl
						label={__("Redirect To", "essential-blocks-pro")}
						value={redirectUrl}
						placeholder={__("example.com", "essential-blocks")}
						onChange={(url) => setAttributes({ redirectUrl: url })}
						help={__('Please use a valid URL', "essential-blocks")}
					/>
				</>
			)}
		</>
	)
};
addFilter(
	"eb_form_block_control_after_confirmation_type",
	"essential-blocks/pro",
	confirmationTypeRedirect,
	10,
	3
);
