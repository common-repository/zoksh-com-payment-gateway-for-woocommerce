
<?php
$this->form_fields = apply_filters( 'wc_offline_form_fields', array(

                'enabled' => array(
                    'title' => __('Enable/Disable', 'wc-zoksh-gateway'),
                    'type' => 'checkbox',
                    'label' => __('Enable zoksh.com', 'wc-zoksh-gateway'),
                    'default' => 'no',
                ),
                'title' => array(
                    'title' => __('Title', 'wc-zoksh-gateway'),
                    'type' => 'text',
                    'description' => __('Title of the payment plugin that user sees during checkout', 'wc-zoksh-gateway'),
                    'default' => __('zoksh', 'wc-zoksh-gateway'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Description', 'wc-zoksh-gateway'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout', 'wc-zoksh-gateway'),
                    'default' => __('Expand your payment options with zoksh! Pay with anything you like!', 'wc-zoksh-gateway'),
                    'desc_tip'    => true,
                ),
                'instructions' => array(
					'title'       => __( 'Instructions', 'wc-zoksh-gateway' ),
					'type'        => 'textarea',
					'description' => __( 'This controls the instructions user sees after payment completion on Thank you page', 'wc-gateway-offline' ),
					'default'     => '',
					'desc_tip'    => true,
				),
                'network' => array(
                    'title' => __('Select Network', 'wc-zoksh-gateway'),
                    'type' => 'select',
                    'label' => __('Testnet/Mainnet', 'wc-zoksh-gateway'),
                    'default' => 'testnet',
                    'options' => array(
                        'testnet' => 'testnet',
                        'mainnet' => 'mainnet'
                   ) 
                ),
                'api_key' => array(
                    'title' => __('Api Key', 'wc-zoksh-gateway'),
                    'type' => 'text',
                    'description' => __('Please enter your zoksh Api Key', 'wc-zoksh-gateway'),
                    'default' => '',
                ),
                'api_secret' => array(
                    'title' => __('Api Secret', 'wc-zoksh-gateway'),
                    'type' => 'text',
                    'description' => __('Please enter your zoksh Api Secret', 'wc-zoksh-gateway'),
                    'default' => '',
                ),
                'simple_total' => array(
                    'title' => __('Compatibility Mode', 'wc-zoksh-gateway'),
                    'type' => 'checkbox',
                    'label' => __("This may be needed for compatibility with certain addons if the order total isn't correct", 'wc-zoksh-gateway'),
                    'default' => '',
                ),
                'invoice_prefix' => array(
                    'title' => __('Invoice Prefix', 'wc-zoksh-gateway'),
                    'type' => 'text',
                    'description' => __('Please enter a prefix for your invoice numbers. If you use your zoksh.com account for multiple stores ensure this prefix is unique.', 'wc-zoksh-gateway'),
                    'default' => 'WC-',
                    'desc_tip' => true,
                )
            ) );