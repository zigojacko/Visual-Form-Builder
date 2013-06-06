<?php
/*
Plugin Name: Visual Form Builder
Description: Dynamically build forms using a simple interface. Forms include jQuery validation, a basic logic-based verification system, and entry tracking.
Author: Matthew Muro
Author URI: http://matthewmuro.com
Version: 2.7.5
*/

// Version number to output as meta tag
define( 'VFB_VERSION', '2.7.5' );

/*
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Instantiate new class
$visual_form_builder = new Visual_Form_Builder();

// Visual Form Builder class
class Visual_Form_Builder{

	/**
	 * The DB version. Used for SQL install and upgrades.
	 *
	 * Should only be changed when needing to change SQL
	 * structure or custom capabilities.
	 *
	 * @since 1.0
	 * @var string
	 * @access protected
	 */
	protected $vfb_db_version = '2.7';

	/**
	 * Flag used to add scripts to front-end only once
	 *
	 * @since 1.0
	 * @var string
	 * @access protected
	 */
	protected $add_scripts = false;

	/**
	 * An array of countries to be used throughout plugin
	 *
	 * @since 1.0
	 * @var array
	 * @access public
	 */
	public $countries = array( "", "Afghanistan", "Albania", "Algeria", "Andorra", "Angola", "Antigua and Barbuda", "Argentina", "Armenia", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bhutan", "Bolivia", "Bosnia and Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Central African Republic", "Chad", "Chile", "China", "Colombi", "Comoros", "Congo (Brazzaville)", "Congo", "Costa Rica", "Cote d\'Ivoire", "Croatia", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor (Timor Timur)", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Fiji", "Finland", "France", "Gabon", "Gambia, The", "Georgia", "Germany", "Ghana", "Greece", "Grenada", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Honduras", "Hungary", "Iceland", "India", "Indonesia", "Iran", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, North", "Korea, South", "Kuwait", "Kyrgyzstan", "Laos", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libya", "Liechtenstein", "Lithuania", "Luxembourg", "Macedonia", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Mauritania", "Mauritius", "Mexico", "Micronesia", "Moldova", "Monaco", "Mongolia", "Montenegro", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepa", "Netherlands", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Poland", "Portugal", "Qatar", "Romania", "Russia", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Serbia", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "Spain", "Sri Lanka", "Sudan", "Suriname", "Swaziland", "Sweden", "Switzerland", "Syria", "Taiwan", "Tajikistan", "Tanzania", "Thailand", "Togo", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States of America", "Uruguay", "Uzbekistan", "Vanuatu", "Vatican City", "Venezuela", "Vietnam", "Yemen", "Zambia", "Zimbabwe" );

	/**
	 * Admin page menu hooks
	 *
	 * @since 2.7.2
	 * @var array
	 * @access private
	 */
	private $_admin_pages = array();

	/**
	 * Constructor. Register core filters and actions.
	 *
	 * @access public
	 */
	public function __construct(){
		global $wpdb;

		// Setup global database table names
		$this->field_table_name 	= $wpdb->prefix . 'visual_form_builder_fields';
		$this->form_table_name 		= $wpdb->prefix . 'visual_form_builder_forms';
		$this->entries_table_name 	= $wpdb->prefix . 'visual_form_builder_entries';

		// Make sure we are in the admin before proceeding.
		if ( is_admin() ) {
			// Build options and settings pages.
			add_action( 'admin_menu', array( &$this, 'add_admin' ) );
			add_action( 'admin_init', array( &$this, 'save' ) );
			add_action( 'admin_menu', array( &$this, 'additional_plugin_setup' ) );

			// Register AJAX functions
			$actions = array(
				// Form Builder
				'sort_field',
				'create_field',
				'delete_field',
				'form_settings',

				// Media button
				'media_button',
			);

			// Add all AJAX functions
			foreach( $actions as $name ) {
				add_action( "wp_ajax_visual_form_builder_$name", array( &$this, "ajax_$name" ) );
			}

			// Adds additional media button to insert form shortcode
			add_action( 'media_buttons', array( &$this, 'add_media_button' ), 999 );

			// Adds a Dashboard widget
			add_action( 'wp_dashboard_setup', array( &$this, 'add_dashboard_widget' ) );

			// Adds a Settings link to the Plugins page
			add_filter( 'plugin_action_links', array( &$this, 'plugin_action_links' ), 10, 2 );

			// Check the db version and run SQL install, if needed
			add_action( 'plugins_loaded', array( &$this, 'update_db_check' ) );

			// Display update messages
			add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
		}

		// Load i18n
		add_action( 'plugins_loaded', array( &$this, 'languages' ) );

		// Print meta keyword
		add_action( 'wp_head', array( &$this, 'add_meta_keyword' ) );

		add_shortcode( 'vfb', array( &$this, 'form_code' ) );
		add_action( 'init', array( &$this, 'email' ), 10 );
		add_action( 'init', array( &$this, 'confirmation' ), 12 );

		// Add CSS to the front-end
		add_action( 'wp_enqueue_scripts', array( &$this, 'css' ) );
	}

	/**
	 * Allow for additional plugin code to be run during admin_init
	 * which is not available during the plugin __construct()
	 *
	 * @since 2.7
	 */
	public function additional_plugin_setup() {

		$page_main = $this->_admin_pages[ 'vfb' ];

		if ( !get_option( 'vfb_dashboard_widget_options' ) ) {
			$widget_options['vfb_dashboard_recent_entries'] = array(
				'items' => 5,
			);
			update_option( 'vfb_dashboard_widget_options', $widget_options );
		}

	}

	/**
	 * Output plugin version number to help with troubleshooting
	 *
	 * @since 2.7.5
	 */
	public function add_meta_keyword() {
		echo sprintf( "\n<meta name='vfb-version' content='%s' />\n", VFB_VERSION );
	}

	/**
	 * Load localization file
	 *
	 * @since 2.7
	 */
	public function languages() {
		load_plugin_textdomain( 'visual-form-builder', false , 'visual-form-builder/languages' );
	}

	/**
	 * Adds extra include files
	 *
	 * @since 1.2
	 */
	public function includes(){
		global $entries_list, $entries_detail;

		// Load the Entries List class
		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/class-entries-list.php' );
		$entries_list = new VisualFormBuilder_Entries_List();

		// Load the Entries Details class
		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/class-entries-detail.php' );
		$entries_detail = new VisualFormBuilder_Entries_Detail();
	}

	public function include_forms_list() {
		global $forms_list;

		// Load the Forms List class
		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/class-forms-list.php' );
		$forms_list = new VisualFormBuilder_Forms_List();
	}

	/**
	 * Add Settings link to Plugins page
	 *
	 * @since 1.8
	 * @return $links array Links to add to plugin name
	 */
	public function plugin_action_links( $links, $file ) {
		if ( $file == plugin_basename( __FILE__ ) )
			$links[] = '<a href="admin.php?page=visual-form-builder">' . __( 'Settings' , 'visual-form-builder') . '</a>';

		return $links;
	}

	/**
	 * Adds the media button image
	 *
	 * @since 2.3
	 */
	public function add_media_button(){
    	if ( current_user_can( 'manage_options' ) ) :
?>
			<a href="<?php echo add_query_arg( array( 'action' => 'visual_form_builder_media_button', 'width' => '450' ), admin_url( 'admin-ajax.php' ) ); ?>" class="button add_media thickbox" title="Add Visual Form Builder form">
				<img width="18" height="18" src="<?php echo plugins_url( 'visual-form-builder/images/vfb_icon.png' ); ?>" alt="<?php _e( 'Add Visual Form Builder form', 'visual-form-builder' ); ?>" style="vertical-align: middle; margin-left: -8px; margin-top: -2px;" /> <?php _e( 'Add Form', 'visual-form-builder' ); ?>
			</a>
<?php
		endif;
	}

	/**
	 * Adds the dashboard widget
	 *
	 * @since 2.7
	 */
	public function add_dashboard_widget() {
		wp_add_dashboard_widget( 'vfb-dashboard', __( 'Recent Visual Form Builder Entries', 'visual-form-builder' ), array( &$this, 'dashboard_widget' ), array( &$this, 'dashboard_widget_control' ) );
	}

	/**
	 * Displays the dashboard widget content
	 *
	 * @since 2.7
	 */
	public function dashboard_widget() {
		global $wpdb;

		// Get the date/time format that is saved in the options table
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		$widgets = get_option( 'vfb_dashboard_widget_options' );
		$total_items = isset( $widgets['vfb_dashboard_recent_entries'] ) && isset( $widgets['vfb_dashboard_recent_entries']['items'] ) ? absint( $widgets['vfb_dashboard_recent_entries']['items'] ) : 5;

		$forms = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->form_table_name}" );

		if ( !$forms ) :
			echo sprintf(
				'<p>%1$s <a href="%2$s">%3$s</a></p>',
				__( 'You currently do not have any forms.', 'visual-form-builder' ),
				esc_url( admin_url( 'admin.php?page=vfb-add-new' ) ),
				__( 'Get started!', 'visual-form-builder' )
			);

			return;
		endif;

		$entries = $wpdb->get_results( $wpdb->prepare( "SELECT forms.form_title, entries.entries_id, entries.form_id, entries.sender_name, entries.sender_email, entries.date_submitted FROM $this->form_table_name AS forms INNER JOIN $this->entries_table_name AS entries ON entries.form_id = forms.form_id ORDER BY entries.date_submitted DESC LIMIT %d", $total_items ) );

		if ( !$entries ) :
			echo sprintf( '<p>%1$s</p>', __( 'You currently do not have any entries.', 'visual-form-builder' ) );
		else :

			$content = '';

			foreach ( $entries as $entry ) :

				$content .= sprintf(
					'<li><a href="%1$s">%4$s</a> via <a href="%2$s">%5$s</a> <span class="rss-date">%6$s</span><cite>%3$s</cite></li>',
					esc_url( add_query_arg( array( 'action' => 'view', 'entry' => absint( $entry->entries_id ) ), admin_url( 'admin.php?page=vfb-entries' ) ) ),
					esc_url( add_query_arg( 'form-filter', absint( $entry->form_id ), admin_url( 'admin.php?page=vfb-entries' ) ) ),
					esc_html( $entry->sender_name ),
					esc_html( $entry->sender_email ),
					esc_html( $entry->form_title ),
					date( "$date_format $time_format", strtotime( $entry->date_submitted ) )
				);

			endforeach;

			echo "<div class='rss-widget'><ul>$content</ul></div>";

		endif;
	}

	/**
	 * Displays the dashboard widget form control
	 *
	 * @since 2.7
	 */
	public function dashboard_widget_control() {
		if ( !$widget_options = get_option( 'vfb_dashboard_widget_options' ) )
			$widget_options = array();

		if ( !isset( $widget_options['vfb_dashboard_recent_entries'] ) )
			$widget_options['vfb_dashboard_recent_entries'] = array();

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset( $_POST['vfb-widget-recent-entries'] ) ) {
			$number = absint( $_POST['vfb-widget-recent-entries']['items'] );
			$widget_options['vfb_dashboard_recent_entries']['items'] = $number;
			update_option( 'vfb_dashboard_widget_options', $widget_options );
		}

		$number = isset( $widget_options['vfb_dashboard_recent_entries']['items'] ) ? (int) $widget_options['vfb_dashboard_recent_entries']['items'] : '';

		echo sprintf(
			'<p>
			<label for="comments-number">%1$s</label>
			<input id="comments-number" name="vfb-widget-recent-entries[items]" type="text" value="%2$d" size="3" />
			</p>',
			__( 'Number of entries to show:', 'visual-form-builder' ),
			$number
		);
	}

	/**
	 * Register contextual help. This is for the Help tab dropdown
	 *
	 * @since 1.0
	 */
	public function help(){
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-getting-started',
			'title' => 'Getting Started',
			'content' => '<ul>
						<li>Click on the + tab, give your form a name and click Create Form.</li>
						<li>Select form fields from the box on the left and click a field to add it to your form.</li>
						<li>Edit the information for each form field by clicking on the down arrow.</li>
						<li>Drag and drop the elements to put them in order.</li>
						<li>Click Save Form to save your changes.</li>
					</ul>'
		) );

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-item-config',
			'title' => 'Form Item Configuration',
			'content' => "<ul>
						<li><em>Name</em> will change the display name of your form input.</li>
						<li><em>Description</em> will be displayed below the associated input.</li>
						<li><em>Validation</em> allows you to select from several of jQuery's Form Validation methods for text inputs. For more about the types of validation, read the <em>Validation</em> section below.</li>
						<li><em>Required</em> is either Yes or No. Selecting 'Yes' will make the associated input a required field and the form will not submit until the user fills this field out correctly.</li>
						<li><em>Options</em> will only be active for Radio and Checkboxes.  This field contols how many options are available for the associated input.</li>
						<li><em>Size</em> controls the width of Text, Textarea, Select, and Date Picker input fields.  The default is set to Medium but if you need a longer text input, select Large.</li>
						<li><em>CSS Classes</em> allow you to add custom CSS to a field.  This option allows you to fine tune the look of the form.</li>
					</ul>"
		) );

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-validation',
			'title' => 'Validation',
			'content' => "<p>Visual Form Builder uses the <a href='http://docs.jquery.com/Plugins/Validation/Validator'>jQuery Form Validation plugin</a> to perform clientside form validation.</p>
					<ul>

						<li><em>Email</em>: makes the element require a valid email.</li>
						<li><em>URL</em>: makes the element require a valid url.</li>
						<li><em>Date</em>: makes the element require a date. <a href='http://docs.jquery.com/Plugins/Validation/Methods/date'>Refer to documentation for various accepted formats</a>.
						<li><em>Number</em>: makes the element require a decimal number.</li>
						<li><em>Digits</em>: makes the element require digits only.</li>
						<li><em>Phone</em>: makes the element require a US or International phone number. Most formats are accepted.</li>
						<li><em>Time</em>: choose either 12- or 24-hour time format (NOTE: only available with the Time field).</li>
					</ul>"
		) );

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-confirmation',
			'title' => 'Confirmation',
			'content' => "<p>Each form allows you to customize the confirmation by selecing either a Text Message, a WordPress Page, or to Redirect to a URL.</p>
					<ul>
						<li><em>Text</em> allows you to enter a custom formatted message that will be displayed on the page after your form is submitted. HTML is allowed here.</li>
						<li><em>Page</em> displays a dropdown of all WordPress Pages you have created. Select one to redirect the user to that page after your form is submitted.</li>
						<li><em>Redirect</em> will only accept URLs and can be used to send the user to a different site completely, if you choose.</li>
					</ul>"
		) );

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-notification',
			'title' => 'Notification',
			'content' => "<p>Send a customized notification email to the user when the form has been successfully submitted.</p>
					<ul>
						<li><em>Sender Name</em>: the name that will be displayed on the email.</li>
						<li><em>Sender Email</em>: the email that will be used as the Reply To email.</li>
						<li><em>Send To</em>: the email where the notification will be sent. This must be a required text field with email validation.</li>
						<li><em>Subject</em>: the subject of the email.</li>
						<li><em>Message</em>: additional text that can be displayed in the body of the email. HTML tags are allowed.</li>
						<li><em>Include a Copy of the User's Entry</em>: appends a copy of the user's submitted entry to the notification email.</li>
					</ul>"
		) );

		$screen->add_help_tab( array(
			'id' => 'vfb-help-tab-tips',
			'title' => 'Tips',
			'content' => "<ul>
						<li>Fieldsets, a way to group form fields, are an essential piece of this plugin's HTML. As such, at least one fieldset is required and must be first in the order. Subsequent fieldsets may be placed wherever you would like to start your next grouping of fields.</li>
						<li>Security verification is automatically included on very form. It's a simple logic question and should keep out most, if not all, spam bots.</li>
						<li>There is a hidden spam field, known as a honey pot, that should also help deter potential abusers of your form.</li>
						<li>Nesting is allowed underneath fieldsets and sections.  Sections can be nested underneath fieldsets.  Nesting is not required, however, it does make reorganizing easier.</li>
					</ul>"
		) );
	}

	/**
	 * Adds the Screen Options tab to the Entries screen
	 *
	 * @since 1.0
	 */
	public function screen_options(){
		$screen = get_current_screen();

		$page_main		= $this->_admin_pages[ 'vfb' ];
		$page_entries 	= $this->_admin_pages[ 'vfb-entries' ];

		switch( $screen->id ) {
			case $page_entries :

				add_screen_option( 'per_page', array(
					'label'		=> __( 'Entries per page', 'visual-form-builder' ),
					'default'	=> 20,
					'option'	=> 'vfb_entries_per_page'
				) );

			break;

			case $page_main :

				if ( isset( $_REQUEST['form'] ) ) :
					add_screen_option( 'layout_columns', array(
						'max'		=> 2,
						'default'	=> 2
					) );
				else :
					add_screen_option( 'per_page', array(
						'label'		=> __( 'Forms per page', 'visual-form-builder' ),
						'default'	=> 20,
						'option'	=> 'vfb_forms_per_page'
					) );
				endif;

			break;
		}
	}

	/**
	 * Saves the Screen Options
	 *
	 * @since 1.0
	 */
	public function save_screen_options( $status, $option, $value ){

		if ( $option == 'vfb_entries_per_page' )
				return $value;
		elseif ( $option == 'vfb_forms_per_page' )
				return $value;
	}

	/**
	 * Add meta boxes to form builder screen
	 *
	 * @since 1.8
	 */
	public function add_meta_boxes() {
		global $current_screen;

		$page_main = $this->_admin_pages[ 'vfb' ];

		if ( $current_screen->id == $page_main && isset( $_REQUEST['form'] ) ) {
			add_meta_box( 'vfb_form_items_meta_box', __( 'Form Items', 'visual-form-builder' ), array( &$this, 'meta_box_form_items' ), $page_main, 'side', 'high' );
			add_meta_box( 'vfb_form_media_button_tip', __( 'Display Forms', 'visual-form-builder' ), array( &$this, 'meta_box_display_forms' ), $page_main, 'side', 'low' );
		}
	}
	/**
	 * Output for Form Items meta box
	 *
	 * @since 1.8
	 */
	public function meta_box_form_items() {
	?>
		<div class="taxonomydiv">
			<p><strong><?php _e( 'Click' , 'visual-form-builder'); ?></strong> <?php _e( 'to Add a Field' , 'visual-form-builder'); ?> <img id="add-to-form" alt="" src="<?php echo admin_url( '/images/wpspin_light.gif' ); ?>" class="waiting spinner" /></p>
			<ul class="posttype-tabs add-menu-item-tabs" id="vfb-field-tabs">
				<li class="tabs"><a href="#standard-fields" class="nav-tab-link vfb-field-types"><?php _e( 'Standard' , 'visual-form-builder'); ?></a></li>
			</ul>
			<div id="standard-fields" class="tabs-panel tabs-panel-active">
				<ul class="vfb-fields-col-1">
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-fieldset">Fieldset</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-text"><b></b>Text</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-checkbox"><b></b>Checkbox</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-select"><b></b>Select</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-datepicker"><b></b>Date</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-url"><b></b>URL</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-digits"><b></b>Number</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-phone"><b></b>Phone</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-file"><b></b>File Upload</a></li>
				</ul>
				<ul class="vfb-fields-col-2">
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-section"><b></b>Section</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-textarea"><b></b>Textarea</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-radio"><b></b>Radio</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-address"><b></b>Address</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-email"><b></b>Email</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-currency"><b></b>Currency</a></li>
					<li><a href="#" class="vfb-draggable-form-items" id="form-element-time"><b></b>Time</a></li>

					<li><a href="#" class="vfb-draggable-form-items" id="form-element-html"><b></b>HTML</a></li>

					<li><a href="#" class="vfb-draggable-form-items" id="form-element-instructions"><b></b>Instructions</a></li>
				</ul>
				<div class="clear"></div>
			</div> <!-- #standard-fields -->
		</div> <!-- .taxonomydiv -->
		<div class="clear"></div>
	<?php
	}

	/**
	 * Output for the Display Forms meta box
	 *
	 * @since 1.8
	 */
	public function meta_box_display_forms() {
	?>
		<p><?php _e( 'Add forms to your Posts or Pages by locating the icon shown below in the area above your post/page editor.', 'visual-form-builder' ); ?><br>
    		<img src="<?php echo plugins_url( 'visual-form-builder/images/media-button-help.png' ); ?>">
    	</p>
    	<p><?php _e( 'You may also manually insert the shortcode into a post/page.', 'visual-form-builder' ); ?></p>
    	<p><?php _e( 'Shortcode', 'visual-form-builder' ); ?> <code>[vfb id='<?php echo (int) $_REQUEST['form']; ?>']</code></p>
	<?php
	}

	/**
	 * Check database version and run SQL install, if needed
	 *
	 * @since 2.1
	 */
	public function update_db_check() {
		// Add a database version to help with upgrades and run SQL install
		if ( !get_option( 'vfb_db_version' ) ) {
			update_option( 'vfb_db_version', $this->vfb_db_version );
			$this->install_db();
		}

		// If database version doesn't match, update and run SQL install
		if ( version_compare( get_option( 'vfb_db_version' ), $this->vfb_db_version, '<' ) ) {
			update_option( 'vfb_db_version', $this->vfb_db_version );
			$this->install_db();
		}
	}

	/**
	 * Install database tables
	 *
	 * @since 1.0
	 */
	static function install_db() {
		global $wpdb;

		$field_table_name     = $wpdb->prefix . 'visual_form_builder_fields';
		$form_table_name      = $wpdb->prefix . 'visual_form_builder_forms';
		$entries_table_name   = $wpdb->prefix . 'visual_form_builder_entries';

		// Explicitly set the character set and collation when creating the tables
		$charset = ( defined( 'DB_CHARSET' && '' !== DB_CHARSET ) ) ? DB_CHARSET : 'utf8';
		$collate = ( defined( 'DB_COLLATE' && '' !== DB_COLLATE ) ) ? DB_COLLATE : 'utf8_general_ci';

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$field_sql = "CREATE TABLE $field_table_name (
				field_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				form_id BIGINT(20) NOT NULL,
				field_key VARCHAR(255) NOT NULL,
				field_type VARCHAR(25) NOT NULL,
				field_options TEXT,
				field_description TEXT,
				field_name TEXT NOT NULL,
				field_sequence BIGINT(20) DEFAULT '0',
				field_parent BIGINT(20) DEFAULT '0',
				field_validation VARCHAR(25),
				field_required VARCHAR(25),
				field_size VARCHAR(25) DEFAULT 'medium',
				field_css VARCHAR(255),
				field_layout VARCHAR(255),
				field_default TEXT,
				PRIMARY KEY  (field_id)
			) DEFAULT CHARACTER SET $charset COLLATE $collate;";

		$form_sql = "CREATE TABLE $form_table_name (
				form_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				form_key TINYTEXT NOT NULL,
				form_title TEXT NOT NULL,
				form_email_subject TEXT,
				form_email_to TEXT,
				form_email_from VARCHAR(255),
				form_email_from_name VARCHAR(255),
				form_email_from_override VARCHAR(255),
				form_email_from_name_override VARCHAR(255),
				form_success_type VARCHAR(25) DEFAULT 'text',
				form_success_message TEXT,
				form_notification_setting VARCHAR(25),
				form_notification_email_name VARCHAR(255),
				form_notification_email_from VARCHAR(255),
				form_notification_email VARCHAR(25),
				form_notification_subject VARCHAR(255),
				form_notification_message TEXT,
				form_notification_entry VARCHAR(25),
				form_label_alignment VARCHAR(25),
				PRIMARY KEY  (form_id)
			) DEFAULT CHARACTER SET $charset COLLATE $collate;";

		$entries_sql = "CREATE TABLE $entries_table_name (
				entries_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				form_id BIGINT(20) NOT NULL,
				data LONGTEXT NOT NULL,
				subject TEXT,
				sender_name VARCHAR(255),
				sender_email VARCHAR(255),
				emails_to TEXT,
				date_submitted DATETIME,
				ip_address VARCHAR(25),
				entry_approved VARCHAR(20) DEFAULT '1',
				PRIMARY KEY  (entries_id)
			) DEFAULT CHARACTER SET $charset COLLATE $collate;";

		// Create or Update database tables
		dbDelta( $field_sql );
		dbDelta( $form_sql );
		dbDelta( $entries_sql );
	}

	/**
	 * Queue plugin scripts for sorting form fields
	 *
	 * @since 1.0
	 */
	public function admin_scripts() {
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'jquery-form-validation', plugins_url( '/js/jquery.validate.min.js', __FILE__ ), array( 'jquery' ), '1.9.0', true );
		wp_enqueue_script( 'vfb-admin', plugins_url( '/js/vfb-admin.js', __FILE__ ) , array( 'jquery', 'jquery-form-validation' ), '', true );
		wp_enqueue_script( 'nested-sortable', plugins_url( '/js/jquery.ui.nestedSortable.js', __FILE__ ) , array( 'jquery', 'jquery-ui-sortable' ), '1.3.5', true );

		wp_enqueue_style( 'visual-form-builder-style', plugins_url( '/css/visual-form-builder-admin.css', __FILE__ ) );

		wp_localize_script( 'vfb-admin', 'VfbAdminPages', array( 'vfb_pages' => $this->_admin_pages ) );
	}

	/**
	 * Queue form validation scripts
	 *
	 * @since 1.0
	 */
	public function scripts() {
		// Make sure scripts are only added once via shortcode
		$this->add_scripts = true;

		wp_register_script( 'jquery-form-validation', plugins_url( '/js/jquery.validate.min.js', __FILE__ ), array( 'jquery' ), '1.9.0', true );
		wp_register_script( 'visual-form-builder-validation', plugins_url( '/js/vfb-validation.js', __FILE__ ) , array( 'jquery', 'jquery-form-validation' ), '', true );
		wp_register_script( 'visual-form-builder-metadata', plugins_url( '/js/jquery.metadata.js', __FILE__ ) , array( 'jquery', 'jquery-form-validation' ), '', true );
		wp_register_script( 'vfb-ckeditor', plugins_url( '/js/ckeditor/ckeditor.js', __FILE__ ), array( 'jquery' ), '4.1', true );

		wp_enqueue_script( 'jquery-form-validation' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'visual-form-builder-validation' );
		wp_enqueue_script( 'visual-form-builder-metadata' );
		wp_enqueue_script( 'vfb-ckeditor' );
	}

	/**
	 * Add form CSS to wp_head
	 *
	 * @since 1.0
	 */
	public function css() {
		wp_register_style( 'vfb-jqueryui-css', apply_filters( 'vfb-date-picker-css', plugins_url( '/css/smoothness/jquery-ui-1.9.2.min.css', __FILE__ ) ) );
		wp_register_style( 'visual-form-builder-css', apply_filters( 'visual-form-builder-css', plugins_url( '/css/visual-form-builder.css', __FILE__ ) ) );

		wp_enqueue_style( 'visual-form-builder-css' );
		wp_enqueue_style( 'vfb-jqueryui-css' );
	}

	/**
	 * Actions to save, update, and delete forms/form fields
	 *
	 *
	 * @since 1.0
	 */
	public function save() {
		global $wpdb;

		if ( !isset( $_REQUEST['page'] ) )
			return;

		if ( !isset( $_REQUEST['action'] ) )
			return;

		if ( in_array( $_REQUEST['page'], array( 'visual-form-builder', 'vfb-add-new', 'vfb-entries' ) ) ) :
			switch ( $_REQUEST['action'] ) :
				case 'create_form' :

					check_admin_referer( 'create_form' );

					$form_key 		= sanitize_title( $_REQUEST['form_title'] );
					$form_title 	= esc_html( $_REQUEST['form_title'] );
					$form_from_name = esc_html( $_REQUEST['form_email_from_name'] );
					$form_subject 	= esc_html( $_REQUEST['form_email_subject'] );
					$form_from 		= esc_html( $_REQUEST['form_email_from'] );
					$form_to 		= serialize( $_REQUEST['form_email_to'] );

					$newdata = array(
						'form_key' 				=> $form_key,
						'form_title' 			=> $form_title,
						'form_email_from_name'	=> $form_from_name,
						'form_email_subject'	=> $form_subject,
						'form_email_from'		=> $form_from,
						'form_email_to'			=> $form_to,
						'form_success_message'	=> '<p id="form_success">Your form was successfully submitted. Thank you for contacting us.</p>'
					);

					// Create the form
					$wpdb->insert( $this->form_table_name, $newdata );

					// Get form ID to add our first field
					$new_form_selected = $wpdb->insert_id;

					// Setup the initial fieldset
					$initial_fieldset = array(
						'form_id' 			=> $wpdb->insert_id,
						'field_key' 		=> 'fieldset',
						'field_type' 		=> 'fieldset',
						'field_name' 		=> 'Fieldset',
						'field_sequence' 	=> 0
					);

					// Add the first fieldset to get things started
					$wpdb->insert( $this->field_table_name, $initial_fieldset );

					$verification_fieldset = array(
						'form_id' 			=> $new_form_selected,
						'field_key' 		=> 'verification',
						'field_type' 		=> 'verification',
						'field_name' 		=> 'Verification',
						'field_description' => '(This is for preventing spam)',
						'field_sequence' 	=> 1
					);

					// Insert the submit field
					$wpdb->insert( $this->field_table_name, $verification_fieldset );

					$verify_fieldset_parent_id = $wpdb->insert_id;

					$secret = array(
						'form_id' 			=> $new_form_selected,
						'field_key' 		=> 'secret',
						'field_type' 		=> 'secret',
						'field_name' 		=> 'Please enter any two digits with no spaces (Example: 12)',
						'field_size' 		=> 'medium',
						'field_required' 	=> 'yes',
						'field_parent' 		=> $verify_fieldset_parent_id,
						'field_sequence' 	=> 2
					);

					// Insert the submit field
					$wpdb->insert( $this->field_table_name, $secret );

					// Make the submit last in the sequence
					$submit = array(
						'form_id' 			=> $new_form_selected,
						'field_key' 		=> 'submit',
						'field_type' 		=> 'submit',
						'field_name' 		=> 'Submit',
						'field_parent' 		=> $verify_fieldset_parent_id,
						'field_sequence' 	=> 3
					);

					// Insert the submit field
					$wpdb->insert( $this->field_table_name, $submit );

					// Redirect to keep the URL clean (use AJAX in the future?)
					wp_redirect( 'admin.php?page=visual-form-builder&form=' . $new_form_selected );
					exit();

				break;

				case 'update_form' :

					check_admin_referer( 'vfb_update_form' );

					$form_id 						= absint( $_REQUEST['form_id'] );
					$form_key 						= sanitize_title( $_REQUEST['form_title'], $form_id );
					$form_title 					= $_REQUEST['form_title'];
					$form_subject 					= $_REQUEST['form_email_subject'];
					$form_to 						= serialize( array_map( 'sanitize_email', $_REQUEST['form_email_to'] ) );
					$form_from 						= sanitize_email( $_REQUEST['form_email_from'] );
					$form_from_name 				= $_REQUEST['form_email_from_name'];
					$form_from_override 			= isset( $_REQUEST['form_email_from_override'] ) ? $_REQUEST['form_email_from_override'] : '';
					$form_from_name_override 		= isset( $_REQUEST['form_email_from_name_override'] ) ? $_REQUEST['form_email_from_name_override'] : '';
					$form_success_type 				= $_REQUEST['form_success_type'];
					$form_notification_setting 		= isset( $_REQUEST['form_notification_setting'] ) ? $_REQUEST['form_notification_setting'] : '';
					$form_notification_email_name 	= isset( $_REQUEST['form_notification_email_name'] ) ? $_REQUEST['form_notification_email_name'] : '';
					$form_notification_email_from 	= isset( $_REQUEST['form_notification_email_from'] ) ? sanitize_email( $_REQUEST['form_notification_email_from'] ) : '';
					$form_notification_email 		= isset( $_REQUEST['form_notification_email'] ) ? $_REQUEST['form_notification_email'] : '';
					$form_notification_subject 		= isset( $_REQUEST['form_notification_subject'] ) ? $_REQUEST['form_notification_subject'] : '';
					$form_notification_message 		= isset( $_REQUEST['form_notification_message'] ) ? wp_richedit_pre( $_REQUEST['form_notification_message'] ) : '';
					$form_notification_entry 		= isset( $_REQUEST['form_notification_entry'] ) ? $_REQUEST['form_notification_entry'] : '';
					$form_label_alignment 			= $_REQUEST['form_label_alignment'];

					// Add confirmation based on which type was selected
					switch ( $form_success_type ) {
						case 'text' :
							$form_success_message = wp_richedit_pre( $_REQUEST['form_success_message_text'] );
						break;
						case 'page' :
							$form_success_message = $_REQUEST['form_success_message_page'];
						break;
						case 'redirect' :
							$form_success_message = $_REQUEST['form_success_message_redirect'];
						break;
					}

					$newdata = array(
						'form_key' 						=> $form_key,
						'form_title' 					=> $form_title,
						'form_email_subject' 			=> $form_subject,
						'form_email_to' 				=> $form_to,
						'form_email_from' 				=> $form_from,
						'form_email_from_name' 			=> $form_from_name,
						'form_email_from_override' 		=> $form_from_override,
						'form_email_from_name_override' => $form_from_name_override,
						'form_success_type' 			=> $form_success_type,
						'form_success_message' 			=> $form_success_message,
						'form_notification_setting' 	=> $form_notification_setting,
						'form_notification_email_name' 	=> $form_notification_email_name,
						'form_notification_email_from' 	=> $form_notification_email_from,
						'form_notification_email' 		=> $form_notification_email,
						'form_notification_subject' 	=> $form_notification_subject,
						'form_notification_message' 	=> $form_notification_message,
						'form_notification_entry' 		=> $form_notification_entry,
						'form_label_alignment' 			=> $form_label_alignment
					);

					$where = array( 'form_id' => $form_id );

					// Update form details
					$wpdb->update( $this->form_table_name, $newdata, $where );

					// Initialize field sequence
					$field_sequence = 0;

					// Loop through each field and update
					foreach ( $_REQUEST['field_id'] as $id ) :
						$id = absint( $id );

						$field_name 		= ( isset( $_REQUEST['field_name-' . $id] ) ) ? $_REQUEST['field_name-' . $id] : '';
						$field_key 			= sanitize_key( sanitize_title( $field_name, $id ) );
						$field_desc 		= ( isset( $_REQUEST['field_description-' . $id] ) ) ? $_REQUEST['field_description-' . $id] : '';
						$field_options 		= ( isset( $_REQUEST['field_options-' . $id] ) ) ? serialize( $_REQUEST['field_options-' . $id] ) : '';
						$field_validation 	= ( isset( $_REQUEST['field_validation-' . $id] ) ) ? $_REQUEST['field_validation-' . $id] : '';
						$field_required 	= ( isset( $_REQUEST['field_required-' . $id] ) ) ? $_REQUEST['field_required-' . $id] : '';
						$field_size 		= ( isset( $_REQUEST['field_size-' . $id] ) ) ? $_REQUEST['field_size-' . $id] : '';
						$field_css 			= ( isset( $_REQUEST['field_css-' . $id] ) ) ? $_REQUEST['field_css-' . $id] : '';
						$field_layout 		= ( isset( $_REQUEST['field_layout-' . $id] ) ) ? $_REQUEST['field_layout-' . $id] : '';
						$field_default 		= ( isset( $_REQUEST['field_default-' . $id] ) ) ? $_REQUEST['field_default-' . $id] : '';

						$field_data = array(
							'field_key' 		=> $field_key,
							'field_name' 		=> $field_name,
							'field_description' => $field_desc,
							'field_options'		=> $field_options,
							'field_validation' 	=> $field_validation,
							'field_required' 	=> $field_required,
							'field_size' 		=> $field_size,
							'field_css' 		=> $field_css,
							'field_layout' 		=> $field_layout,
							'field_sequence' 	=> $field_sequence,
							'field_default' 	=> $field_default
						);

						$where = array(
							'form_id' 	=> $form_id,
							'field_id' 	=> $id
						);

						// Update all fields
						$wpdb->update( $this->field_table_name, $field_data, $where );

						$field_sequence++;
					endforeach;

				break;

				case 'delete_form' :
					$id = absint( $_REQUEST['form'] );

					check_admin_referer( 'delete-form-' . $id );

					// Delete form and all fields
					$wpdb->query( $wpdb->prepare( "DELETE FROM $this->form_table_name WHERE form_id = %d", $id ) );
					$wpdb->query( $wpdb->prepare( "DELETE FROM $this->field_table_name WHERE form_id = %d", $id ) );

					// Redirect to keep the URL clean (use AJAX in the future?)
					wp_redirect( add_query_arg( 'action', 'deleted', 'admin.php?page=visual-form-builder' ) );
					exit();

				break;

				case 'copy_form' :
					$id = absint( $_REQUEST['form'] );

					check_admin_referer( 'copy-form-' . $id );

					// Get all fields and data for the request form
					$fields    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->field_table_name WHERE form_id = %d", $id ) );
					$forms     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->form_table_name WHERE form_id = %d", $id ) );
					$override  = $wpdb->get_var( $wpdb->prepare( "SELECT form_email_from_override, form_email_from_name_override, form_notification_email FROM $this->form_table_name WHERE form_id = %d", $id ) );
					$from_name = $wpdb->get_var( null, 1 );
					$notify    = $wpdb->get_var( null, 2 );

					// Copy this form and force the initial title to denote a copy
					foreach ( $forms as $form ) {
						$data = array(
							'form_key'						=> sanitize_title( $form->form_key . ' copy' ),
							'form_title' 					=> $form->form_title . ' Copy',
							'form_email_subject' 			=> $form->form_email_subject,
							'form_email_to' 				=> $form->form_email_to,
							'form_email_from' 				=> $form->form_email_from,
							'form_email_from_name' 			=> $form->form_email_from_name,
							'form_email_from_override' 		=> $form->form_email_from_override,
							'form_email_from_name_override' => $form->form_email_from_name_override,
							'form_success_type' 			=> $form->form_success_type,
							'form_success_message' 			=> $form->form_success_message,
							'form_notification_setting' 	=> $form->form_notification_setting,
							'form_notification_email_name' 	=> $form->form_notification_email_name,
							'form_notification_email_from' 	=> $form->form_notification_email_from,
							'form_notification_email' 		=> $form->form_notification_email,
							'form_notification_subject' 	=> $form->form_notification_subject,
							'form_notification_message' 	=> $form->form_notification_message,
							'form_notification_entry' 		=> $form->form_notification_entry,
							'form_label_alignment' 			=> $form->form_label_alignment
						);

						$wpdb->insert( $this->form_table_name, $data );
					}

					// Get form ID to add our first field
					$new_form_selected = $wpdb->insert_id;

					// Copy each field and data
					foreach ( $fields as $field ) {
						$data = array(
							'form_id' 			=> $new_form_selected,
							'field_key' 		=> $field->field_key,
							'field_type' 		=> $field->field_type,
							'field_name' 		=> $field->field_name,
							'field_description' => $field->field_description,
							'field_options' 	=> $field->field_options,
							'field_sequence' 	=> $field->field_sequence,
							'field_validation' 	=> $field->field_validation,
							'field_required' 	=> $field->field_required,
							'field_size' 		=> $field->field_size,
							'field_css' 		=> $field->field_css,
							'field_layout' 		=> $field->field_layout,
							'field_parent' 		=> $field->field_parent
						);

						$wpdb->insert( $this->field_table_name, $data );

						// If a parent field, save the old ID and the new ID to update new parent ID
						if ( in_array( $field->field_type, array( 'fieldset', 'section', 'verification' ) ) )
							$parents[ $field->field_id ] = $wpdb->insert_id;

						if ( $override == $field->field_id )
							$wpdb->update( $this->form_table_name, array( 'form_email_from_override' => $wpdb->insert_id ), array( 'form_id' => $new_form_selected ) );

						if ( $from_name == $field->field_id )
							$wpdb->update( $this->form_table_name, array( 'form_email_from_name_override' => $wpdb->insert_id ), array( 'form_id' => $new_form_selected ) );

						if ( $notify == $field->field_id )
							$wpdb->update( $this->form_table_name, array( 'form_notification_email' => $wpdb->insert_id ), array( 'form_id' => $new_form_selected ) );
					}

					// Loop through our parents and update them to their new IDs
					foreach ( $parents as $k => $v ) {
						$wpdb->update( $this->field_table_name, array( 'field_parent' => $v ), array( 'form_id' => $new_form_selected, 'field_parent' => $k ) );
					}

				break;

				case 'trash_entry' :
					$entry_id = absint( $_GET['entry'] );
					$wpdb->update( $this->entries_table_name, array( 'entry_approved' => 'trash' ), array( 'entries_id' => $entry_id ) );
				break;
			endswitch;
		endif;
	}

	/**
	 * The jQuery field sorting callback
	 *
	 * @since 1.0
	 */
	public function ajax_sort_field() {
		global $wpdb;

		$data = array();

		foreach ( $_REQUEST['order'] as $k ) {
			if ( 'root' !== $k['item_id'] ) {
				$data[] = array(
					'field_id' 	=> $k['item_id'],
					'parent' 	=> $k['parent_id']
				);
			}
		}

		foreach ( $data as $k => $v ) {
			// Update each field with it's new sequence and parent ID
			$update = $wpdb->update( $this->field_table_name, array( 'field_sequence' => $k, 'field_parent' => $v['parent'] ), array( 'field_id' => $v['field_id'] ) );
		}

		wp_die();
	}

	/**
	 * The jQuery create field callback
	 *
	 * @since 1.9
	 */
	public function ajax_create_field() {
		global $wpdb;

		$data = array();
		$field_options = $field_validation = '';

		foreach ( $_REQUEST['data'] as $k ) {
			$data[ $k['name'] ] = $k['value'];
		}

		check_ajax_referer( 'create-field-' . $data['form_id'], 'nonce' );

		$form_id 	= absint( $data['form_id'] );
		$field_key 	= sanitize_title( $_REQUEST['field_type'] );
		$field_name = esc_html( $_REQUEST['field_type'] );
		$field_type = strtolower( sanitize_title( $_REQUEST['field_type'] ) );

		// Set defaults for validation
		switch ( $field_type ) {
			case 'select' :
			case 'radio' :
			case 'checkbox' :
				$field_options = serialize( array( 'Option 1', 'Option 2', 'Option 3' ) );
			break;

			case 'email' :
			case 'url' :
			case 'phone' :
				$field_validation = $field_type;
			break;

			case 'currency' :
				$field_validation = 'number';
			break;

			case 'number' :
				$field_validation = 'digits';
			break;

			case 'time' :
				$field_validation = 'time-12';
			break;

			case 'file-upload' :
				$field_options = serialize( array( 'png|jpe?g|gif' ) );
			break;
		}


		// Get the last row's sequence that isn't a Verification
		$sequence_last_row = $wpdb->get_var( $wpdb->prepare( "SELECT field_sequence FROM $this->field_table_name WHERE form_id = %d AND field_type = 'verification' ORDER BY field_sequence DESC LIMIT 1", $form_id ) );

		// If it's not the first for this form, add 1
		$field_sequence = ( !empty( $sequence_last_row ) ) ? $sequence_last_row : 0;

		$newdata = array(
			'form_id' 			=> $form_id,
			'field_key' 		=> $field_key,
			'field_name' 		=> $field_name,
			'field_type' 		=> $field_type,
			'field_options' 	=> $field_options,
			'field_sequence' 	=> $field_sequence,
			'field_validation' 	=> $field_validation
		);

		// Create the field
		$wpdb->insert( $this->field_table_name, $newdata );
		$insert_id = $wpdb->insert_id;

		// VIP fields
		$vip_fields = array( 'verification', 'secret', 'submit' );

		// Move the VIPs
		foreach ( $vip_fields as $update ) {
			$field_sequence++;
			$where = array(
				'form_id' 		=> absint( $data['form_id'] ),
				'field_type' 	=> $update
			);
			$wpdb->update( $this->field_table_name, array( 'field_sequence' => $field_sequence ), $where );

		}

		echo $this->field_output( $data['form_id'], $insert_id );

		die(1);
	}

	/**
	 * The jQuery delete field callback
	 *
	 * @since 1.9
	 */
	public function ajax_delete_field() {
		global $wpdb;

		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'visual_form_builder_delete_field' ) {
			$form_id = absint( $_REQUEST['form'] );
			$field_id = absint( $_REQUEST['field'] );

			check_ajax_referer( 'delete-field-' . $form_id, 'nonce' );

			if ( isset( $_REQUEST['child_ids'] ) ) {
				foreach ( $_REQUEST['child_ids'] as $children ) {
					$parent = absint( $_REQUEST['parent_id'] );

					// Update each child item with the new parent ID
					$wpdb->update( $this->field_table_name, array( 'field_parent' => $parent ), array( 'field_id' => $children ) );
				}
			}

			// Delete the field
			$wpdb->query( $wpdb->prepare( "DELETE FROM $this->field_table_name WHERE field_id = %d", $field_id ) );
		}

		die(1);
	}

	/**
	 * The jQuery form settings callback
	 *
	 * @since 2.2
	 */
	public function ajax_form_settings() {
		global $current_user;
		get_currentuserinfo();

		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'visual_form_builder_form_settings' ) {
			$form_id 	= absint( $_REQUEST['form'] );
			$status 	= isset( $_REQUEST['status'] ) ? $_REQUEST['status'] : 'opened';
			$accordion 	= isset( $_REQUEST['accordion'] ) ? $_REQUEST['accordion'] : 'general-settings';
			$user_id 	= $current_user->ID;

			$form_settings = get_user_meta( $user_id, 'vfb-form-settings', true );

			$array = array(
				'form_setting_tab' 	=> $status,
				'setting_accordion' => $accordion
			);

			// Set defaults if meta key doesn't exist
			if ( !$form_settings || $form_settings == '' ) {
				$meta_value[ $form_id ] = $array;

				update_user_meta( $user_id, 'vfb-form-settings', $meta_value );
			}
			else {
				$form_settings[ $form_id ] = $array;

				update_user_meta( $user_id, 'vfb-form-settings', $form_settings );
			}
		}

		die(1);
	}

	/**
	 * Display the additional media button
	 *
	 * Used for inserting the form shortcode with desired form ID
	 *
	 * @since 2.3
	 */
	public function ajax_media_button(){
		global $wpdb;

		// Sanitize the sql orderby
		$order = sanitize_sql_orderby( 'form_id ASC' );

		// Build our forms as an object
		$forms = $wpdb->get_results( "SELECT form_id, form_title FROM $this->form_table_name ORDER BY $order" );
	?>
<script type="text/javascript">
	jQuery(document).ready(function($) {
	    $( '#add_vfb_form' ).submit(function(e){
	        e.preventDefault();

	        window.send_to_editor( '[vfb id=' + $( '#vfb_forms' ).val() + ']' );

	        window.tb_remove();
	    });
	});
</script>
<div id="vfb_form">
	<form id="add_vfb_form" class="media-upload-form type-form validate">
		<h3 class="media-title">Insert Visual Form Builder Form</h3>
		<p>Select a form below to insert into any Post or Page.</p>
		<select id="vfb_forms" name="vfb_forms">
			<?php foreach( $forms as $form ) : ?>
				<option value="<?php echo $form->form_id; ?>"><?php echo $form->form_title; ?></option>
			<?php endforeach; ?>
		</select>
		<p><input type="submit" class="button-primary" value="Insert Form" /></p>
	</form>
</div>
	<?php
		die(1);
	}

	/**
	 * All Forms output in admin
	 *
	 * @since 2.5
	 */
	public function all_forms() {
		global $wpdb, $forms_list;

		$order = sanitize_sql_orderby( 'form_title ASC' );

		$where = apply_filters( 'vfb_pre_get_forms', '' );
		$forms = $wpdb->get_results( "SELECT form_id, form_title FROM $this->form_table_name WHERE 1=1 $where ORDER BY $order" );

		if ( !$forms ) :
			echo '<div class="vfb-form-alpha-list"><h3 id="vfb-no-forms">You currently do not have any forms.  <a href="' . esc_url( admin_url( 'admin.php?page=vfb-add-new' ) ) . '">Click here to get started</a>.</h3></div>';
			return;
		endif;

		echo '<form id="forms-filter" method="post" action="">';

		$forms_list->prepare_items();

    	$forms_list->search_box( 'search', 'search_id' );
    	$forms_list->display();

		echo '</form>';
?>

	<?php
	}

	/**
	 * Build field output in admin
	 *
	 * @since 1.9
	 */
	public function field_output( $form_nav_selected_id, $field_id = NULL ) {
		global $wpdb;

		$field_where = ( isset( $field_id ) && !is_null( $field_id ) ) ? "AND field_id = $field_id" : '';
		// Display all fields for the selected form
		$fields = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->field_table_name WHERE form_id = %d $field_where ORDER BY field_sequence ASC", $form_nav_selected_id ) );

		$depth = 1;
		$parent = $last = 0;
		ob_start();

		// Loop through each field and display
		foreach ( $fields as $field ) :
			// If we are at the root level
			if ( !$field->field_parent && $depth > 1 ) {
				// If we've been down a level, close out the list
				while ( $depth > 1 ) {
					echo '</li></ul>';
					$depth--;
				}

				// Close out the root item
				echo '</li>';
			}
			// first item of <ul>, so move down a level
			elseif ( $field->field_parent && $field->field_parent == $last ) {
				echo '<ul class="parent">';
				$depth++;
			}
			// Close up a <ul> and move up a level
			elseif ( $field->field_parent && $field->field_parent != $parent ) {
				echo '</li></ul></li>';
				$depth--;
			}
			// Same level so close list item
			elseif ( $field->field_parent && $field->field_parent == $parent )
				echo '</li>';

			// Store item ID and parent ID to test for nesting
			$last = $field->field_id;
			$parent = $field->field_parent;
	?>
<li id="form_item_<?php echo $field->field_id; ?>" class="form-item<?php echo ( in_array( $field->field_type, array( 'submit', 'secret', 'verification' ) ) ) ? ' ui-state-disabled' : ''; ?><?php echo ( !in_array( $field->field_type, array( 'fieldset', 'section', 'verification' ) ) ) ? ' mjs-nestedSortable-no-nesting' : ''; ?>">
		<dl class="menu-item-bar">
			<dt class="vfb-menu-item-handle vfb-menu-item-type-<?php echo esc_attr( $field->field_type ); ?>">
				<span class="item-title"><?php echo stripslashes( esc_attr( $field->field_name ) ); ?><?php echo ( $field->field_required == 'yes' ) ? ' <span class="is-field-required">*</span>' : ''; ?></span>
                <span class="item-controls">
					<span class="item-type"><?php echo strtoupper( str_replace( '-', ' ', $field->field_type ) ); ?></span>
					<a href="#" title="<?php _e( 'Edit Field Item' , 'visual-form-builder'); ?>" id="edit-<?php echo $field->field_id; ?>" class="item-edit"><?php _e( 'Edit Field Item' , 'visual-form-builder'); ?></a>
				</span>
			</dt>
		</dl>

		<div id="form-item-settings-<?php echo $field->field_id; ?>" class="menu-item-settings field-type-<?php echo $field->field_type; ?>" style="display: none;">
			<?php if ( in_array( $field->field_type, array( 'fieldset', 'section', 'verification' ) ) ) : ?>

				<p class="description description-wide">
					<label for="edit-form-item-name-<?php echo $field->field_id; ?>"><?php echo ( in_array( $field->field_type, array( 'fieldset', 'verification' ) ) ) ? 'Legend' : 'Name'; ?>
                    <span class="vfb-tooltip" rel="<?php esc_attr_e( 'For Fieldsets, a Legend is simply the name of that group. Use general terms that describe the fields included in this Fieldset.', 'visual-form-builder' ); ?>" title="<?php esc_attr_e( 'About Legend', 'visual-form-builder' ); ?>">(?)</span>
                        <br />
						<input type="text" value="<?php echo stripslashes( esc_attr( $field->field_name ) ); ?>" name="field_name-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-name-<?php echo $field->field_id; ?>" maxlength="255" />
					</label>
				</p>
                <p class="description description-wide">
                    <label for="edit-form-item-css-<?php echo $field->field_id; ?>">
                        <?php _e( 'CSS Classes' , 'visual-form-builder'); ?>
                        <span class="vfb-tooltip" rel="<?php esc_attr_e( 'For each field, you can insert your own CSS class names which can be used in your own stylesheets.', 'visual-form-builder' ); ?>" title="<?php esc_attr_e( 'About CSS Classes', 'visual-form-builder' ); ?>">(?)</span>
                        <br />
                        <input type="text" value="<?php echo stripslashes( esc_attr( $field->field_css ) ); ?>" name="field_css-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-css-<?php echo $field->field_id; ?>" />
                    </label>
                </p>

			<?php elseif( $field->field_type == 'instructions' ) : ?>
				<!-- Instructions -->
				<p class="description description-wide">
					<label for="edit-form-item-name-<?php echo $field->field_id; ?>">
							<?php _e( 'Name' , 'visual-form-builder'); ?>
                            <span class="vfb-tooltip" title="<?php esc_attr_e( 'About Name', 'visual-form-builder' ); ?>" rel="<?php esc_attr_e( "A field's name is the most visible and direct way to describe what that field is for.", 'visual-form-builder' ); ?>">(?)</span>
                            <br />
							<input type="text" value="<?php echo stripslashes( esc_attr( $field->field_name ) ); ?>" name="field_name-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-name-<?php echo $field->field_id; ?>" maxlength="255" />
					</label>
				</p>
				<p class="description description-wide">
					<label for="edit-form-item-description-<?php echo $field->field_id; ?>">
                    	<?php _e( 'Description (HTML tags allowed)', 'visual-form-builder' ); ?>
                    	<span class="vfb-tooltip" title="<?php esc_attr_e( 'About Instructions Description', 'visual-form-builder' ); ?>" rel="<?php esc_attr_e( 'The Instructions field allows for long form explanations, typically seen at the beginning of Fieldsets or Sections. HTML tags are allowed.', 'visual-form-builder' ); ?>">(?)</span>
                        <br />
						<textarea name="field_description-<?php echo $field->field_id; ?>" class="widefat edit-menu-item-description" cols="20" rows="3" id="edit-form-item-description-<?php echo $field->field_id; ?>" /><?php echo stripslashes( $field->field_description ); ?></textarea>
					</label>
				</p>

			<?php else: ?>

				<!-- Name -->
				<p class="description description-wide">
					<label for="edit-form-item-name-<?php echo $field->field_id; ?>">
						<?php _e( 'Name' , 'visual-form-builder'); ?>
                        <span class="vfb-tooltip" title="<?php esc_attr_e( 'About Name', 'visual-form-builder' ); ?>" rel="<?php esc_attr_e( "A field's name is the most visible and direct way to describe what that field is for.", 'visual-form-builder' ); ?>">(?)</span>
                        <br />
						<input type="text" value="<?php echo stripslashes( esc_attr( $field->field_name ) ); ?>" name="field_name-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-name-<?php echo $field->field_id; ?>" maxlength="255" />
					</label>
				</p>
				<?php if ( $field->field_type == 'submit' ) : ?>
					<!-- CSS Classes -->
                    <p class="description description-wide">
                        <label for="edit-form-item-css-<?php echo $field->field_id; ?>">
                            <?php _e( 'CSS Classes' , 'visual-form-builder'); ?>
                            <span class="vfb-tooltip" rel="<?php esc_attr_e( 'For each field, you can insert your own CSS class names which can be used in your own stylesheets.', 'visual-form-builder' ); ?>" title="<?php esc_attr_e( 'About CSS Classes', 'visual-form-builder' ); ?>">(?)</span>
                            <br />
                            <input type="text" value="<?php echo stripslashes( esc_attr( $field->field_css ) ); ?>" name="field_css-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-css-<?php echo $field->field_id; ?>" />
                        </label>
                    </p>
				<?php elseif ( $field->field_type !== 'submit' ) : ?>
					<!-- Description -->
					<p class="description description-wide">
						<label for="edit-form-item-description-<?php echo $field->field_id; ?>">
							<?php _e( 'Description' , 'visual-form-builder'); ?>
                             <span class="vfb-tooltip" title="<?php esc_attr_e( 'About Description', 'visual-form-builder' ); ?>" rel="<?php esc_attr_e( 'A description is an optional piece of text that further explains the meaning of this field. Descriptions are displayed below the field. HTML tags are allowed.', 'visual-form-builder' ); ?>">(?)</span>
                            <br />
							<textarea name="field_description-<?php echo $field->field_id; ?>" class="widefat edit-menu-item-description" cols="20" rows="3" id="edit-form-item-description-<?php echo $field->field_id; ?>" /><?php echo stripslashes( $field->field_description ); ?></textarea>
						</label>
					</p>

					<?php
						// Display the Options input only for radio, checkbox, and select fields
						if ( in_array( $field->field_type, array( 'radio', 'checkbox', 'select' ) ) ) : ?>
						<!-- Options -->
						<p class="description description-wide">
							<?php _e( 'Options' , 'visual-form-builder'); ?>
                            <span class="vfb-tooltip" title="<?php esc_attr_e( 'About Options', 'visual-form-builder' ); ?>" rel="<?php esc_attr_e( 'This property allows you to set predefined options to be selected by the user.  Use the plus and minus buttons to add and delete options.  At least one option must exist.', 'visual-form-builder' ); ?>">(?)</span>
                            <br />
						<?php
							// If the options field isn't empty, unserialize and build array
							if ( !empty( $field->field_options ) ) {
								if ( is_serialized( $field->field_options ) )
									$opts_vals = ( is_array( unserialize( $field->field_options ) ) ) ? unserialize( $field->field_options ) : explode( ',', unserialize( $field->field_options ) );
							}
							// Otherwise, present some default options
							else
								$opts_vals = array( 'Option 1', 'Option 2', 'Option 3' );

							// Basic count to keep track of multiple options
							$count = 1;

							// Loop through the options
							foreach ( $opts_vals as $options ) {
						?>
						<div id="clone-<?php echo $field->field_id . '-' . $count; ?>" class="option">
							<label for="edit-form-item-options-<?php echo $field->field_id . "-$count"; ?>" class="clonedOption">
								<input type="radio" value="<?php echo esc_attr( $count ); ?>" name="field_default-<?php echo $field->field_id; ?>" <?php checked( $field->field_default, $count ); ?> />
								<input type="text" value="<?php echo stripslashes( esc_attr( $options ) ); ?>" name="field_options-<?php echo $field->field_id; ?>[]" class="widefat" id="edit-form-item-options-<?php echo $field->field_id . "-$count"; ?>" />
							</label>

							<a href="#" class="addOption" title="Add an Option"><?php _e( 'Add', 'visual-form-builder' ); ?></a> <a href="#" class="deleteOption" title="Delete Option"><?php _e( 'Delete', 'visual-form-builder' ); ?></a>
						</div>
						   <?php
								$count++;
							}
							?>
						</p>
					<?php
						// Unset the options for any following radio, checkboxes, or selects
						unset( $opts_vals );
						endif;
					?>

					<?php
						// Display the Options input only for radio, checkbox, select, and autocomplete fields
						if ( in_array( $field->field_type, array( 'file-upload' ) ) ) :
					?>
                    	<!-- File Upload Accepts -->
						<p class="description description-wide">
                            <?php
							$opts_vals = array( '' );

							// If the options field isn't empty, unserialize and build array
							if ( !empty( $field->field_options ) ) {
								if ( is_serialized( $field->field_options ) )
									$opts_vals = ( is_array( unserialize( $field->field_options ) ) ) ? unserialize( $field->field_options ) : unserialize( $field->field_options );
							}

							// Loop through the options
							foreach ( $opts_vals as $options ) {
						?>
							<label for="edit-form-item-options-<?php echo $field->field_id; ?>">
								<?php _e( 'Accepted File Extensions' , 'visual-form-builder'); ?>
                                <span class="vfb-tooltip" title="<?php esc_attr_e( 'About Accepted File Extensions', 'visual-form-builder' ); ?>" rel="<?php esc_attr_e( 'Control the types of files allowed.  Enter extensions without periods and separate multiples using the pipe character ( | ).', 'visual-form-builder' ); ?>">(?)</span>
                        		<br />
                                <input type="text" value="<?php echo stripslashes( esc_attr( $options ) ); ?>" name="field_options-<?php echo $field->field_id; ?>[]" class="widefat" id="edit-form-item-options-<?php echo $field->field_id; ?>" />
							</label>
                        </p>
                    <?php
							}
						// Unset the options for any following radio, checkboxes, or selects
						unset( $opts_vals );
						endif;
					?>
					<!-- Validation -->
					<p class="description description-thin">
						<label for="edit-form-item-validation">
							<?php _e( 'Validation' , 'visual-form-builder'); ?>
                            <span class="vfb-tooltip" title="<?php esc_attr_e( 'About Validation', 'visual-form-builder' ); ?>" rel="<?php esc_attr_e( 'Ensures user-entered data is formatted properly. For more information on Validation, refer to the Help tab at the top of this page.', 'visual-form-builder' ); ?>">(?)</span>
                            <br />

						   <?php if ( in_array( $field->field_type , array( 'text', 'time', 'number' ) ) ) : ?>
							   <select name="field_validation-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-validation-<?php echo $field->field_id; ?>">
							   		<?php if ( $field->field_type == 'time' ) : ?>
									<option value="time-12" <?php selected( $field->field_validation, 'time-12' ); ?>><?php _e( '12 Hour Format' , 'visual-form-builder'); ?></option>
									<option value="time-24" <?php selected( $field->field_validation, 'time-24' ); ?>><?php _e( '24 Hour Format' , 'visual-form-builder'); ?></option>
									<?php elseif ( in_array( $field->field_type, array( 'number' ) ) ) : ?>
	                                <option value="number" <?php selected( $field->field_validation, 'number' ); ?>><?php _e( 'Number' , 'visual-form-builder'); ?></option>
									<option value="digits" <?php selected( $field->field_validation, 'digits' ); ?>><?php _e( 'Digits' , 'visual-form-builder'); ?></option>
									<?php else : ?>
									<option value="" <?php selected( $field->field_validation, '' ); ?>><?php _e( 'None' , 'visual-form-builder'); ?></option>
									<option value="email" <?php selected( $field->field_validation, 'email' ); ?>><?php _e( 'Email' , 'visual-form-builder'); ?></option>
									<option value="url" <?php selected( $field->field_validation, 'url' ); ?>><?php _e( 'URL' , 'visual-form-builder'); ?></option>
									<option value="date" <?php selected( $field->field_validation, 'date' ); ?>><?php _e( 'Date' , 'visual-form-builder'); ?></option>
									<option value="number" <?php selected( $field->field_validation, 'number' ); ?>><?php _e( 'Number' , 'visual-form-builder'); ?></option>
									<option value="digits" <?php selected( $field->field_validation, 'digits' ); ?>><?php _e( 'Digits' , 'visual-form-builder'); ?></option>
									<option value="phone" <?php selected( $field->field_validation, 'phone' ); ?>><?php _e( 'Phone' , 'visual-form-builder'); ?></option>
									<?php endif; ?>
							   </select>
						   <?php else :
							   $field_validation = '';

							   switch ( $field->field_type ) {
								   case 'email' :
									case 'url' :
									case 'phone' :
										$field_validation = $field->field_type;
									break;

									case 'currency' :
										$field_validation = 'number';
									break;

									case 'number' :
										$field_validation = 'digits';
									break;
							   }

						   ?>
						   <input type="text" class="widefat" name="field_validation-<?php echo $field->field_id; ?>" value="<?php echo $field_validation; ?>" readonly="readonly" />
						   <?php endif; ?>

						</label>
					</p>

					<!-- Required -->
					<p class="field-link-target description description-thin">
						<label for="edit-form-item-required">
							<?php _e( 'Required' , 'visual-form-builder'); ?>
                            <span class="vfb-tooltip" title="<?php esc_attr_e( 'About Required', 'visual-form-builder' ); ?>" rel="<?php esc_attr_e( 'Requires the field to be completed before the form is submitted. By default, all fields are set to No.', 'visual-form-builder' ); ?>">(?)</span>
                            <br />
							<select name="field_required-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-required-<?php echo $field->field_id; ?>">
								<option value="no" <?php selected( $field->field_required, 'no' ); ?>><?php _e( 'No' , 'visual-form-builder'); ?></option>
								<option value="yes" <?php selected( $field->field_required, 'yes' ); ?>><?php _e( 'Yes' , 'visual-form-builder'); ?></option>
							</select>
						</label>
					</p>

					<?php if ( !in_array( $field->field_type, array( 'radio', 'checkbox', 'time' ) ) ) : ?>
						<!-- Size -->
						<p class="description description-thin">
							<label for="edit-form-item-size">
								<?php _e( 'Size' , 'visual-form-builder'); ?>
                                <span class="vfb-tooltip" title="<?php esc_attr_e( 'About Size', 'visual-form-builder' ); ?>" rel="<?php esc_attr_e( 'Control the size of the field.  By default, all fields are set to Medium.', 'visual-form-builder' ); ?>">(?)</span>
                                <br />
								<select name="field_size-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-size-<?php echo $field->field_id; ?>">
                                	<option value="small" <?php selected( $field->field_size, 'small' ); ?>><?php _e( 'Small' , 'visual-form-builder'); ?></option>
									<option value="medium" <?php selected( $field->field_size, 'medium' ); ?>><?php _e( 'Medium' , 'visual-form-builder'); ?></option>
									<option value="large" <?php selected( $field->field_size, 'large' ); ?>><?php _e( 'Large' , 'visual-form-builder'); ?></option>
								</select>
							</label>
						</p>

					<?php elseif ( in_array( $field->field_type, array( 'radio', 'checkbox', 'time' ) ) ) : ?>
						<!-- Options Layout -->
						<p class="description description-thin">
							<label for="edit-form-item-size">
								<?php _e( 'Options Layout' , 'visual-form-builder'); ?>
                                <span class="vfb-tooltip" title="<?php esc_attr_e( 'About Options Layout', 'visual-form-builder' ); ?>" rel="<?php esc_attr_e( 'Control the layout of radio buttons or checkboxes.  By default, options are arranged in One Column.', 'visual-form-builder' ); ?>">(?)</span>
                                <br />
								<select name="field_size-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-size-<?php echo $field->field_id; ?>"<?php echo ( $field->field_type == 'time' ) ? ' disabled="disabled"' : ''; ?>>
									<option value="" <?php selected( $field->field_size, '' ); ?>><?php _e( 'One Column' , 'visual-form-builder'); ?></option>
                                    <option value="two-column" <?php selected( $field->field_size, 'two-column' ); ?>><?php _e( 'Two Columns' , 'visual-form-builder'); ?></option>
									<option value="three-column" <?php selected( $field->field_size, 'three-column' ); ?>><?php _e( 'Three Columns' , 'visual-form-builder'); ?></option>
                                    <option value="auto-column" <?php selected( $field->field_size, 'auto-column' ); ?>><?php _e( 'Auto Width' , 'visual-form-builder'); ?></option>
								</select>
							</label>
						</p>

					<?php endif; ?>
						<!-- Field Layout -->
						<p class="description description-thin">
							<label for="edit-form-item-layout">
								<?php _e( 'Field Layout' , 'visual-form-builder'); ?>
                                <span class="vfb-tooltip" title="<?php esc_attr_e( 'About Field Layout', 'visual-form-builder' ); ?>" rel="<?php esc_attr_e( 'Used to create advanced layouts. Align fields side by side in various configurations.', 'visual-form-builder' ); ?>">(?)</span>
                                <br />
								<select name="field_layout-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-layout-<?php echo $field->field_id; ?>">

									<option value="" <?php selected( $field->field_layout, '' ); ?>><?php _e( 'Default' , 'visual-form-builder'); ?></option>
                                    <optgroup label="------------">
                                    <option value="left-half" <?php selected( $field->field_layout, 'left-half' ); ?>><?php _e( 'Left Half' , 'visual-form-builder'); ?></option>
                                    <option value="right-half" <?php selected( $field->field_layout, 'right-half' ); ?>><?php _e( 'Right Half' , 'visual-form-builder'); ?></option>
                                    </optgroup>
                                    <optgroup label="------------">
									<option value="left-third" <?php selected( $field->field_layout, 'left-third' ); ?>><?php _e( 'Left Third' , 'visual-form-builder'); ?></option>
                                    <option value="middle-third" <?php selected( $field->field_layout, 'middle-third' ); ?>><?php _e( 'Middle Third' , 'visual-form-builder'); ?></option>
                                    <option value="right-third" <?php selected( $field->field_layout, 'right-third' ); ?>><?php _e( 'Right Third' , 'visual-form-builder'); ?></option>
                                    </optgroup>
                                    <optgroup label="------------">
                                    <option value="left-two-thirds" <?php selected( $field->field_layout, 'left-two-thirds' ); ?>><?php _e( 'Left Two Thirds' , 'visual-form-builder'); ?></option>
                                    <option value="right-two-thirds" <?php selected( $field->field_layout, 'right-two-thirds' ); ?>><?php _e( 'Right Two Thirds' , 'visual-form-builder'); ?></option>
                                    </optgroup>
								</select>
							</label>
						</p>
					<?php if ( !in_array( $field->field_type, array( 'radio', 'select', 'checkbox', 'time', 'address' ) ) ) : ?>
					<!-- Default Value -->
					<p class="description description-wide">
                        <label for="edit-form-item-default-<?php echo $field->field_id; ?>">
                            <?php _e( 'Default Value' , 'visual-form-builder'); ?>
                            <span class="vfb-tooltip" title="<?php esc_attr_e( 'About Default Value', 'visual-form-builder' ); ?>" rel="<?php esc_attr_e( 'Set a default value that will be inserted automatically.', 'visual-form-builder' ); ?>">(?)</span>
                        	<br />
                            <input type="text" value="<?php echo stripslashes( esc_attr( $field->field_default ) ); ?>" name="field_default-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-default-<?php echo $field->field_id; ?>" maxlength="255" />
                        </label>
					</p>
					<?php elseif( in_array( $field->field_type, array( 'address' ) ) ) : ?>
					<!-- Default Country -->
					<p class="description description-wide">
                        <label for="edit-form-item-default-<?php echo $field->field_id; ?>">
                            <?php _e( 'Default Country' , 'visual-form-builder'); ?>
                            <span class="vfb-tooltip" title="<?php esc_attr_e( 'About Default Country', 'visual-form-builder' ); ?>" rel="<?php esc_attr_e( 'Select the country you would like to be displayed by default.', 'visual-form-builder' ); ?>">(?)</span>
                        	<br />
                            <select name="field_default-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-default-<?php echo $field->field_id; ?>">
                            <?php
                            foreach ( $this->countries as $country ) {
								echo '<option value="' . $country . '" ' . selected( $field->field_default, $country, 0 ) . '>' . $country . '</option>';
							}
							?>
							</select>
                        </label>
					</p>
					<?php endif; ?>
					<!-- CSS Classes -->
					<p class="description description-wide">
                        <label for="edit-form-item-css-<?php echo $field->field_id; ?>">
                            <?php _e( 'CSS Classes' , 'visual-form-builder'); ?>
                            <span class="vfb-tooltip" title="<?php esc_attr_e( 'About CSS Classes', 'visual-form-builder' ); ?>" rel="<?php esc_attr_e( 'For each field, you can insert your own CSS class names which can be used in your own stylesheets.', 'visual-form-builder' ); ?>">(?)</span>
                            <br />
                            <input type="text" value="<?php echo stripslashes( esc_attr( $field->field_css ) ); ?>" name="field_css-<?php echo $field->field_id; ?>" class="widefat" id="edit-form-item-css-<?php echo $field->field_id; ?>" maxlength="255" />
                        </label>
					</p>

				<?php endif; ?>
			<?php endif; ?>

			<?php if ( !in_array( $field->field_type, array( 'verification', 'secret', 'submit' ) ) ) : ?>
				<div class="menu-item-actions description-wide submitbox">
					<a href="<?php echo esc_url( wp_nonce_url( admin_url('admin.php?page=visual-form-builder&amp;action=delete_field&amp;form=' . $form_nav_selected_id . '&amp;field=' . $field->field_id ), 'delete-field-' . $form_nav_selected_id ) ); ?>" class="item-delete submitdelete deletion"><?php _e( 'Remove' , 'visual-form-builder'); ?></a>
				</div>
			<?php endif; ?>

		<input type="hidden" name="field_id[<?php echo $field->field_id; ?>]" value="<?php echo $field->field_id; ?>" />
		</div>
	<?php
		endforeach;

		// This assures all of the <ul> and <li> are closed
		if ( $depth > 1 ) {
			while( $depth > 1 ) {
				echo '</li>
					</ul>';
				$depth--;
			}
		}

		// Close out last item
		echo '</li>';
		echo ob_get_clean();
	}

	/**
	 * Display admin notices
	 *
	 * @since 1.0
	 */
	public function admin_notices(){
		if ( isset( $_REQUEST['action'] ) ) {
			switch( $_REQUEST['action'] ) {
				case 'create_form' :
					echo '<div id="message" class="updated"><p>' . __( 'The form has been successfully created.' , 'visual-form-builder' ) . '</p></div>';
				break;
				case 'update_form' :
					echo '<div id="message" class="updated"><p>' . sprintf( __( 'The %s form has been updated.' , 'visual-form-builder'), '<strong>' . $_REQUEST['form_title'] . '</strong>' ) . '</p></div>';
				break;
				case 'deleted' :
					echo '<div id="message" class="updated"><p>' . __( 'The form has been successfully deleted.' , 'visual-form-builder') . '</p></div>';
				break;
				case 'copy_form' :
					echo '<div id="message" class="updated"><p>' . __( 'The form has been successfully duplicated.' , 'visual-form-builder') . '</p></div>';
				break;
			}

		}
	}

	/**
	 * Add options page to Settings menu
	 *
	 *
	 * @since 1.0
	 * @uses add_options_page() Creates a menu item under the Settings menu.
	 */
	public function add_admin() {
		$current_pages = array();

		$current_pages[ 'vfb' ] = add_menu_page( __( 'Visual Form Builder', 'visual-form-builder' ), __( 'Visual Form Builder', 'visual-form-builder' ), 'manage_options', 'visual-form-builder', array( &$this, 'admin' ), plugins_url( 'visual-form-builder/images/vfb_icon.png' ) );

		add_submenu_page( 'visual-form-builder', __( 'Visual Form Builder', 'visual-form-builder' ), __( 'All Forms', 'visual-form-builder' ), 'manage_options', 'visual-form-builder', array( &$this, 'admin' ) );
		$current_pages[ 'vfb-add-new' ] = add_submenu_page( 'visual-form-builder', __( 'Add New Form', 'visual-form-builder' ), __( 'Add New Form', 'visual-form-builder' ), 'manage_options', 'vfb-add-new', array( &$this, 'admin_add_new' ) );
		$current_pages[ 'vfb-entries' ] = add_submenu_page( 'visual-form-builder', __( 'Entries', 'visual-form-builder' ), __( 'Entries', 'visual-form-builder' ), 'manage_options', 'vfb-entries', array( &$this, 'admin_entries' ) );
		$current_pages[ 'vfb-export' ] = add_submenu_page( 'visual-form-builder', __( 'Export', 'visual-form-builder' ), __( 'Export', 'visual-form-builder' ), 'manage_options', 'vfb-export', array( &$this, 'admin_export' ) );

		// All plugin page load hooks
		foreach ( $current_pages as $key => $page ) :
			// Load the jQuery and CSS we need if we're on our plugin page
			add_action( "load-$page", array( &$this, 'admin_scripts' ) );

			// Load the Help tab on all pages
			add_action( "load-$page", array( &$this, 'help' ) );
		endforeach;

		// Save pages array for filter/action use throughout plugin
		$this->_admin_pages = $current_pages;

		// Adds a Screen Options tab to the Entries screen
		add_filter( 'load-' . $current_pages['vfb'], array( &$this, 'screen_options' ) );
		add_filter( 'load-' . $current_pages['vfb-entries'], array( &$this, 'screen_options' ) );

		// Add meta boxes to the form builder admin page
		add_action( 'load-' . $current_pages['vfb'], array( &$this, 'add_meta_boxes' ) );

		// Include Entries and Import files
		add_action( 'load-' . $current_pages['vfb-entries'], array( &$this, 'includes' ) );

		add_action( 'load-' . $current_pages['vfb'], array( &$this, 'include_forms_list' ) );
	}

	/**
	 * Display Add New Form page
	 *
	 *
	 * @since 2.7.2
	 */
	public function admin_add_new() {
?>
	<div class="wrap">
		<?php screen_icon( 'options-general' ); ?>
		<h2><?php _e( 'Add New Form', 'visual-form-builder' ); ?></h2>
<?php
		include_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/admin-new-form.php' );
?>
	</div>
<?php
	}

	/**
	 * Display Entries
	 *
	 *
	 * @since 2.7.2
	 */
	public function admin_entries() {
		global $entries_list, $entries_detail;
?>
	<div class="wrap">
		<?php screen_icon( 'options-general' ); ?>
		<h2>
			<?php _e( 'Entries', 'visual-form-builder' ); ?>
<?php
			// If searched, output the query
			if ( isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) )
				echo '<span class="subtitle">' . sprintf( __( 'Search results for "%s"' , 'visual-form-builder' ), $_REQUEST['s'] );
?>
		</h2>
<?php
		if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array( 'view', 'edit', 'update_entry' ) ) ) :
			$entries_detail->entries_detail();
		else :
			$entries_list->views();
			$entries_list->prepare_items();
?>
    	<form id="entries-filter" method="post" action="">
<?php
        	$entries_list->search_box( 'search', 'search_id' );
        	$entries_list->display();
?>
        </form>
	<?php endif; ?>
	</div>
<?php
	}

	/**
	 * Display Export
	 *
	 *
	 * @since 2.7.2
	 */
	public function admin_export() {
		global $export;
?>
	<div class="wrap">
		<?php screen_icon( 'options-general' ); ?>
		<h2><?php _e( 'Export', 'visual-form-builder' ); ?></h2>
<?php
		$export->display();
?>
	</div>
<?php
	}

	/**
	 * Builds the options settings page
	 *
	 * @since 1.0
	 */
	public function admin() {
		global $wpdb, $current_user;

		get_currentuserinfo();

		// Save current user ID
		$user_id = $current_user->ID;

		// Set variables depending on which tab is selected
		$form_nav_selected_id = ( isset( $_REQUEST['form'] ) ) ? $_REQUEST['form'] : '0';
	?>
	<div class="wrap">
		<?php screen_icon( 'options-general' ); ?>
		<h2>
			<?php _e( 'Visual Form Builder', 'visual-form-builder' ); ?>
<?php
			// Add New link
			echo sprintf( ' <a href="%1$s" class="add-new-h2">%2$s</a>', esc_url( admin_url( 'admin.php?page=vfb-add-new' ) ), esc_html( __( 'Add New', 'visual-form-builder' ) ) );

			// If searched, output the query
			if ( isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) )
				echo '<span class="subtitle">' . sprintf( __( 'Search results for "%s"' , 'visual-form-builder' ), $_REQUEST['s'] );
?>
		</h2>
		<?php if ( empty( $form_nav_selected_id ) ) : ?>
		<div id="vfb-form-list">
			<div id="vfb-sidebar">
				<div id="vfb-upgrade-column">
					<div class="vfb-pro-upgrade">
				    	<h2><a href="http://vfb.matthewmuro.com">Visual Form Builder Pro</a></h2>
				        <p class="vfb-pro-call-to-action">
				        	<a class="vfb-btn vfb-btn-inverse" href="http://vfb.matthewmuro.com/#pricing"><?php _e( 'View Pricing' , 'visual-form-builder'); ?></a>
				        	<a class="vfb-btn vfb-btn-primary" href="http://visualformbuilder.fetchapp.com/sell/dahdaeng/ppc"><?php _e( 'Buy Now' , 'visual-form-builder'); ?></a>
				        </p>
				        <h3><?php _e( 'New Features' , 'visual-form-builder'); ?></h3>
				        <ul>
				        	<li><a href="http://vfb.matthewmuro.com/#addons"><?php _e( 'Now with Add-Ons' , 'visual-form-builder'); ?></a></li>
				            <li><?php _e( 'Akismet Support' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Optional SPAM Verification' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Nested Drag and Drop' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Conditional Logic' , 'visual-form-builder'); ?></li>
				            <li><?php _e( '10+ new Form Fields' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Complete Entries Management' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Import/Export' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Quality HTML Email Template' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Plain Text Email Option' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Email Designer' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Analytics' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Data &amp; Form Migration' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Scheduling' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Limit Form Entries' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Simple PayPal Integration' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Form Paging' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Live Preview' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Custom Capabilities' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'No License Key' , 'visual-form-builder'); ?></li>
				            <li><?php _e( 'Automatic Updates' , 'visual-form-builder'); ?></li>
				        </ul>

				        <p><a href="http://vfb.matthewmuro.com/#features"><?php _e( 'View all features' , 'visual-form-builder'); ?></a>.</p>
				    </div> <!-- .vfb-pro-upgrade -->

			   		<h3><?php _e( 'Promote Visual Form Builder' , 'visual-form-builder'); ?></h3>
			        <ul id="promote-vfb">
			        	<li id="twitter"><?php _e( 'Follow me on Twitter' , 'visual-form-builder'); ?>: <a href="http://twitter.com/#!/matthewmuro">@matthewmuro</a></li>
			            <li id="star"><a href="http://wordpress.org/extend/plugins/visual-form-builder/"><?php _e( 'Rate Visual Form Builder on WordPress.org' , 'visual-form-builder'); ?></a></li>
			            <li id="paypal">
			                <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=G87A9UN9CLPH4&lc=US&item_name=Visual%20Form%20Builder&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted"><img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" width="74" height="21"></a>
			            </li>
			        </ul>
			    </div> <!-- #vfb-upgrade-column -->
			</div> <!-- #vfb-sidebar -->
			<div id="vfb-main" class="vfb-order-type-list">
				<?php $this->all_forms(); ?>
			</div> <!-- #vfb-main -->
		</div> <!-- #vfb-form-list -->

		<?php
		elseif ( !empty( $form_nav_selected_id ) && $form_nav_selected_id !== '0' ) :
			include_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/admin-form-creator.php' );
		endif;
		?>
	</div>
	<?php
	}

	/**
	 * Handle confirmation when form is submitted
	 *
	 * @since 1.3
	 */
	function confirmation(){
		global $wpdb;

		$form_id = ( isset( $_REQUEST['form_id'] ) ) ? (int) esc_html( $_REQUEST['form_id'] ) : '';

		if ( isset( $_REQUEST['visual-form-builder-submit'] ) ) :
			// Get forms
			$order = sanitize_sql_orderby( 'form_id DESC' );
			$forms 	= $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->form_table_name WHERE form_id = %d ORDER BY $order", $form_id ) );

			foreach ( $forms as $form ) :
				// If text, return output and format the HTML for display
				if ( 'text' == $form->form_success_type )
					return stripslashes( html_entity_decode( wp_kses_stripslashes( $form->form_success_message ) ) );
				// If page, redirect to the permalink
				elseif ( 'page' == $form->form_success_type ) {
					$page = get_permalink( $form->form_success_message );
					wp_redirect( $page );
					exit();
				}
				// If redirect, redirect to the URL
				elseif ( 'redirect' == $form->form_success_type ) {
					wp_redirect( esc_url( $form->form_success_message ) );
					exit();
				}
			endforeach;
		endif;
	}

	/**
	 * Output form via shortcode
	 *
	 * @since 1.0
	 */
	public function form_code( $atts, $output = '' ) {

		require( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/form-output.php' );

		return $output;
	}

	/**
	 * Handle emailing the content
	 *
	 * @since 1.0
	 * @uses wp_mail() E-mails a message
	 */
	public function email() {
		require( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/email.php' );
	}

	/**
	 * Validate the input
	 *
	 * @since 2.2
	 */
	public function validate_input( $data, $name, $type, $required ) {

		if ( 'yes' == $required && strlen( $data ) == 0 )
			wp_die( "<h1>$name</h1><br>" . __( 'This field is required and cannot be empty.', 'visual-form-builder' ), $name, array( 'back_link' => true ) );

		if ( strlen( $data ) > 0 ) :
			switch( $type ) :

				case 'email' :
					if ( !is_email( $data ) )
						wp_die( "<h1>$name</h1><br>" . __( 'Not a valid email address', 'visual-form-builder' ), '', array( 'back_link' => true ) );
				break;

				case 'number' :
				case 'currency' :
					if ( !is_numeric( $data ) )
						wp_die( "<h1>$name</h1><br>" . __( 'Not a valid number', 'visual-form-builder' ), '', array( 'back_link' => true ) );
				break;

				case 'phone' :
					if ( strlen( $data ) > 9 && preg_match( '/^((\+)?[1-9]{1,2})?([-\s\.])?((\(\d{1,4}\))|\d{1,4})(([-\s\.])?[0-9]{1,12}){1,2}$/', $data ) )
						return true;
					else
						wp_die( "<h1>$name</h1><br>" . __( 'Not a valid phone number. Most US/Canada and International formats accepted.', 'visual-form-builder' ), '', array( 'back_link' => true ) );
				break;

				case 'url' :
					if ( !preg_match( '|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $data ) )
						wp_die( "<h1>$name</h1><br>" . __( 'Not a valid URL.', 'visual-form-builder' ), '', array( 'back_link' => true ) );
				break;

				default :
					return true;
				break;

			endswitch;
		endif;
	}

	/**
	 * Sanitize the input
	 *
	 * @since 2.5
	 */
	public function sanitize_input( $data, $type ) {
		if ( strlen( $data ) > 0 ) :
			switch( $type ) :
				case 'text' :
					return sanitize_text_field( $data );
				break;

				case 'textarea' :
					return wp_strip_all_tags( $data );
				break;

				case 'email' :
					return sanitize_email( $data );
				break;

				case 'html' :
					return wp_kses_data( force_balance_tags( $data ) );
				break;

				case 'number' :
					return floatval( $data );
				break;

				case 'address' :
					$allowed_html = array( 'br' => array() );
					return wp_kses( $data, $allowed_html );
				break;

				default :
					return wp_kses_data( $data );
				break;
			endswitch;
		endif;
	}

	/**
	 * Make sure the User Agent string is not a SPAM bot
	 *
	 * @since 1.3
	 */
	public function isBot() {
		$bots = apply_filters( 'vfb_blocked_spam_bots', array( 'archiver', 'binlar', 'casper', 'checkprivacy', 'clshttp', 'cmsworldmap', 'comodo', 'curl', 'diavol', 'dotbot', 'email', 'extract', 'feedfinder', 'flicky',  'grab', 'harvest', 'httrack', 'ia_archiver', 'jakarta', 'kmccrew', 'libwww', 'loader', 'miner', 'nikto', 'nutch', 'planetwork', 'purebot', 'pycurl', 'python', 'scan', 'skygrid', 'sucker', 'turnit', 'vikspider', 'wget', 'winhttp', 'youda', 'zmeu', 'zune' ) );

		$isBot = false;

		$user_agent = wp_kses_data( $_SERVER['HTTP_USER_AGENT'] );

		foreach ( $bots as $bot ) {
			if ( stripos( $user_agent, $bot ) !== false )
				$isBot = true;
		}

		if ( empty( $user_agent ) || $user_agent == ' ' )
			$isBot = true;

		return $isBot;
	}

	public function build_array_form_item( $value, $type = '' ) {

		$output = '';

		// Basic check for type when not set
		if ( empty( $type ) ) :
			if ( is_array( $value ) && array_key_exists( 'address', $value ) )
				$type = 'address';
			elseif ( is_array( $value ) && array_key_exists( 'hour', $value ) && array_key_exists( 'min', $value ) )
				$type = 'time';
			elseif ( is_array( $value ) )
				$type = 'checkbox';
			else
				$type = 'default';
		endif;

		// Build array'd form item output
		switch( $type ) :

			case 'time' :
				$output = ( array_key_exists( 'ampm', $value ) ) ? substr_replace( implode( ':', $value ), ' ', 5, 1 ) : implode( ':', $value );
			break;

			case 'address' :

				if ( !empty( $value['address'] ) )
					$output .= $value['address'];

				if ( !empty( $value['address-2'] ) ) {
					if ( !empty( $output ) )
						$output .= '<br>';
					$output .= $value['address-2'];
				}

				if ( !empty( $value['city'] ) ) {
					if ( !empty( $output ) )
						$output .= '<br>';
					$output .= $value['city'];
				}
				if ( !empty( $value['state'] ) ) {
					if ( !empty( $output ) && empty( $value['city'] ) )
						$output .= '<br>';
					elseif ( !empty( $output ) && !empty( $value['city'] ) )
						$output .= ', ';
					$output .= $value['state'];
				}
				if ( !empty( $value['zip'] ) ) {
					if ( !empty( $output ) && ( empty( $value['city'] ) && empty( $value['state'] ) ) )
						$output .= '<br>';
					elseif ( !empty( $output ) && ( !empty( $value['city'] ) || !empty( $value['state'] ) ) )
						$output .= ' ';
					$output .= $value['zip'];
				}
				if ( !empty( $value['country'] ) ) {
					if ( !empty( $output ) )
						$output .= '<br>';
					$output .= $value['country'];
				}

			break;

			case 'checkbox' :

				$output = esc_html( implode( ', ', $value ) );

			break;

			default :

				$output = wp_specialchars_decode( stripslashes( esc_html( $value ) ), ENT_QUOTES );

			break;

		endswitch;

		return $output;
	}
}

// The VFB widget
require( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/class-widget.php' );

// Special case to load Export class so AJAX is registered
require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/class-export.php' );
if ( !isset( $export ) )
	$export = new VisualFormBuilder_Export();
?>
