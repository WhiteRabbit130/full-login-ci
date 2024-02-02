<?php defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->library(array('ion_auth', 'form_validation'));
		$this->load->helper(array('url', 'language'));
		$this->load->model('ion_loans_model');

		$this->form_validation->set_error_delimiters($this->config->item('error_start_delimiter', 'ion_auth'), $this->config->item('error_end_delimiter', 'ion_auth'));

		$this->lang->load('auth');

		if (!$this->ion_auth->logged_in()) {

			$this->load->view('header');
			$this->load->view('footer');
		}
	}

	//log the user in
	function login()
	{
		$this->data['title'] = "Login";

		//validate form input
		$this->form_validation->set_rules('identity', 'Identity', 'required');
		$this->form_validation->set_rules('password', 'Password', 'required');

		$recaptchaResponse = $this->input->post('g-recaptcha-response');

		$recaptchaSecretKey = "6LeNblspAAAAAEsQFgKcCAPI-HIv1zqmvmTobFog"; // Replace with your secret key

		$verificationUrl = "https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecretKey&response=$recaptchaResponse";

		$recaptchaResponseData = json_decode(file_get_contents($verificationUrl));

		if ($this->form_validation->run() == true) {
			if ($recaptchaResponseData->success) {
				//check to see if the user is logging in
				//check for "remember me"
				$remember = (bool) $this->input->post('remember');

				if ($this->ion_auth->login($this->input->post('identity'), $this->input->post('password'), $remember)) {
					$randomVerifyCode = rand(10000000, 99999999);

					$email = $this->input->post('identity');
					$data = array(
						'verifyCode' => $randomVerifyCode
					);

					$this->db->where('email', $email);
					$this->db->update('users', $data);

					// $this->email->clear();
					// $this->email->from($this->config->item('admin_email', 'ion_auth'), $this->config->item('site_title', 'ion_auth'));
					// $this->email->to($user->email);
					// $this->email->subject($this->config->item('site_title', 'ion_auth') . ' - ' . $this->lang->line('email_forgotten_password_subject'));
					// $this->email->message($message);
					$message = 'Dear User,<br><br> Your verification code is: ' . $randomVerifyCode . '<br><br> Please sign in with your verification code';
					$this->email->clear();
					$this->email->from($this->config->item('admin_email', 'ion_auth'), $this->config->item('site_title', 'ion_auth'));
					$this->email->to($email);
					$this->email->subject("Verification Code");
					$this->email->message($message);

					if ($this->email->send()) {
						$data['email'] = $email;
						$this->load->view('auth/verifyCode', $data);
					} else {
						redirect('auth/login', 'refresh');
					}

					//if the login is successful
					//redirect them back to the home page

				} else {
					//if the login was un-successful
					//redirect them back to the login page
					$this->session->set_flashdata('message', $this->ion_auth->errors());
					redirect('auth/login', 'refresh'); //use redirects instead of loading views for compatibility with MY_Controller libraries
				}
			} else {
				//if the login was un-successful
				//redirect them back to the login page
				$this->session->set_flashdata('message', "Human Verification failed");
				redirect('auth/login', 'refresh'); //use redirects instead of loading views for compatibility with MY_Controller libraries
			}
		} else {
			//the user is not logging in so display the login page
			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

			$this->data['identity'] = array(
				'name' => 'identity',
				'id' => 'identity',
				'type' => 'text',
				'value' => $this->form_validation->set_value('identity'),
				'placeholder' => 'Email Address',
				'class'	=> 'form-control'
			);
			$this->data['password'] = array(
				'name' => 'password',
				'id' => 'password',
				'type' => 'password',
				'placeholder' => 'password',
				'class'	=> 'form-control'
			);

			$this->_render_page('auth/login', $this->data);
		}
	}

	//home page
	public function index()
	{
		$this->data['title'] = 'Siblings Solutions';

		$this->load->view('header');
		$this->load->view('footer');

		if (!$this->ion_auth->logged_in()) {
			//redirect them to the login page
			redirect('auth/login', 'refresh');
		} else {

			//get user details
			$this->data['user'] = $this->ion_auth->user()->row();

			// $this->data['loans'] = $this->ion_loans_model->getloans()->result();

			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');


			$this->form_validation->set_rules('amount', 'Amount', 'required');

			if ($this->form_validation->run() == true) {

				//$today = date('Y-m-d H:i:s');		

				//$data = array(
				//'post_group'	=> '1'
				//);

				//$this->ion_update_model->insertPost($data);

			} else {

				//display the create user form
				//set the flash data error message if there is one
				$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

				$this->data['loanformattributes'] = array(
					'name'  		=> 'loanformdata',
					'id'    		=> 'loanform'
				);

				$this->data['loanamount'] = array(
					'name'  		=> 'amount',
					'id'    		=> 'amount',
					'type'  		=> 'text',
					'value' 		=> $this->form_validation->set_value('amount'),
					'class' 		=> 'form-control',
					'placeholder' 	=> 'Amount',
					'required' 		=> 'required'
				);

				$this->data['loaninterest'] = array(
					'name'  		=> 'interest',
					'id'   			=> 'interest',
					'type'  		=> 'text',
					'value' 		=> $this->form_validation->set_value('interest'),
					'class' 		=> 'form-control',
					'placeholder' 	=> 'Interest',
					'required' 		=> 'required'
				);

				$this->data['options'] = $this->ion_loans_model->get_data(1);

				$this->_render_page('auth/index', $this->data);
			}
		}
	}

	function users()
	{

		if ($this->ion_auth->logged_in()) {
			$this->load->view('header');
			$this->load->view('footer');
		}

		if (!$this->ion_auth->logged_in()) {
			//redirect them to the login page
			redirect('auth/login', 'refresh');
		} elseif ($this->ion_auth->is_admin()) //remove this elseif if you want to enable this for non-admins
		{
			//redirect them to the home page because they must be an administrator to view this
			//return show_error('You must be an administrator to view this page.');

			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

			//get user details
			$this->data['user'] = $this->ion_auth->user()->row();

			$this->data['groups'] = $this->ion_auth->groups()->result();

			//list the users
			$this->data['users'] = $this->ion_auth->users()->result();
			foreach ($this->data['users'] as $k => $user) {
				$this->data['users'][$k]->groups = $this->ion_auth->get_users_groups($user->id)->result();
			}

			$tables = $this->config->item('tables', 'ion_auth');

			//validate form input
			$this->form_validation->set_rules('first_name', $this->lang->line('create_user_validation_fname_label'), 'required');
			$this->form_validation->set_rules('last_name', $this->lang->line('create_user_validation_lname_label'), 'required');
			$this->form_validation->set_rules('email', $this->lang->line('create_user_validation_email_label'), 'required|valid_email|is_unique[' . $tables['users'] . '.email]');
			$this->form_validation->set_rules('phone', $this->lang->line('create_user_validation_phone_label'), 'required');
			$this->form_validation->set_rules('company', $this->lang->line('create_user_validation_company_label'), 'required');
			$this->form_validation->set_rules('password', $this->lang->line('create_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[password_confirm]');
			$this->form_validation->set_rules('password_confirm', $this->lang->line('create_user_validation_password_confirm_label'), 'required');
			$this->form_validation->set_rules('groups[]', 'Department', 'required');

			if ($this->form_validation->run() == true) {
				$username = strtolower($this->input->post('first_name')) . ' ' . strtolower($this->input->post('last_name'));
				$email    = strtolower($this->input->post('email'));
				$password = $this->input->post('password');

				$additional_data = array(
					'first_name' => $this->input->post('first_name'),
					'last_name'  => $this->input->post('last_name'),
					'company'    => $this->input->post('company'),
					'phone'      => $this->input->post('phone'),
				);

				$groups = $this->input->post('groups[]');
			}

			if ($this->form_validation->run() == true && $this->ion_auth->register($username, $password, $email, $additional_data, $groups)) {

				//check to see if we are creating the user
				//redirect them back to the admin page
				$this->session->set_flashdata('message', $this->ion_auth->messages());
				redirect("auth/users", 'refresh');
			} else {
				//display the create user form
				//set the flash data error message if there is one
				$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

				$this->data['usersformattributes'] = array(
					'name'  	=> 'usersformdata',
					'id'    	=> 'usersform'
				);

				$this->data['first_name'] = array(
					'name'  => 'first_name',
					'id'    => 'first_name',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('first_name'),
					'class' => 'form-control',
					'placeholder' => 'Firstname'
				);
				$this->data['last_name'] = array(
					'name'  => 'last_name',
					'id'    => 'last_name',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('last_name'),
					'class' => 'form-control',
					'placeholder' => 'Lastname'
				);
				$this->data['email'] = array(
					'name'  => 'email',
					'id'    => 'email',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('email'),
					'class' => 'form-control',
					'placeholder' => 'Email Address'
				);
				$this->data['company'] = array(
					'name'  => 'company',
					'id'    => 'company',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('company'),
					'class' => 'form-control',
					'placeholder' => 'Address'
				);
				$this->data['phone'] = array(
					'name'  => 'phone',
					'id'    => 'phone',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('phone'),
					'class' => 'form-control',
					'placeholder' => 'Contact no.'

				);
				$this->data['password'] = array(
					'name'  => 'password',
					'id'    => 'password',
					'type'  => 'password',
					'value' => $this->form_validation->set_value('password'),
					'class' => 'form-control',
					'placeholder' => 'password',
				);
				$this->data['password_confirm'] = array(
					'name'  => 'password_confirm',
					'id'    => 'password_confirm',
					'type'  => 'password',
					'value' => $this->form_validation->set_value('password_confirm'),
					'class' => 'form-control',
					'placeholder' => 'Confirm Password'
				);

				$this->data['groupformattributes'] = array(
					'name'  	=> 'groupformdata',
					'id'    	=> 'groupform'
				);

				$this->data['group_name'] = array(
					'name'  => 'group_name',
					'id'    => 'group_name',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('group_name'),
					'class' => 'form-control',
					'placeholder' => 'Department'
				);

				$this->data['description'] = array(
					'name'  => 'description',
					'id'    => 'description',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('description'),
					'class' => 'form-control',
					'placeholder' => 'Description'
				);

				$this->_render_page('auth/users', $this->data);
			}
		} else {

			$groupID = $this->ion_auth->get_users_groups()->row()->id;

			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

			//get user details
			$this->data['user'] = $this->ion_auth->user()->row();

			//list the users
			$this->data['users'] = $this->ion_auth->users($groupID)->result();
			foreach ($this->data['users'] as $k => $user) {
				$this->data['users'][$k]->groups = $this->ion_auth->get_users_groups($user->id)->result();
			}


			$tables = $this->config->item('tables', 'ion_auth');
			$this->data['usersformattributes'] = array(
				'name'  	=> 'usersformdata',
				'id'    	=> 'usersform'
			);
			//validate form input
			$this->form_validation->set_rules('first_name', $this->lang->line('create_user_validation_fname_label'), 'required');
			$this->form_validation->set_rules('last_name', $this->lang->line('create_user_validation_lname_label'), 'required');
			$this->form_validation->set_rules('email', $this->lang->line('create_user_validation_email_label'), 'required|valid_email|is_unique[' . $tables['users'] . '.email]');
			$this->form_validation->set_rules('phone', $this->lang->line('create_user_validation_phone_label'), 'required');
			$this->form_validation->set_rules('company', $this->lang->line('create_user_validation_company_label'), 'required');
			$this->form_validation->set_rules('password', $this->lang->line('create_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[password_confirm]');
			$this->form_validation->set_rules('password_confirm', $this->lang->line('create_user_validation_password_confirm_label'), 'required');

			if ($this->form_validation->run() == true) {
				$username = strtolower($this->input->post('first_name')) . ' ' . strtolower($this->input->post('last_name'));
				$email    = strtolower($this->input->post('email'));
				$password = $this->input->post('password');

				$additional_data = array(
					'first_name' => $this->input->post('first_name'),
					'last_name'  => $this->input->post('last_name'),
					'company'    => $this->input->post('company'),
					'phone'      => $this->input->post('phone'),
				);

				$group = array($groupID); // Sets user to admin. No need for array('1', '2') as user is always set to member by default
			}

			if ($this->form_validation->run() == true && $this->ion_auth->register($username, $password, $email, $additional_data, $group)) {
				//check to see if we are creating the user
				//redirect them back to the admin page
				$this->session->set_flashdata('message', $this->ion_auth->messages());
				redirect("auth/users", 'refresh');
			} else {
				//display the create user form
				//set the flash data error message if there is one
				$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

				$this->data['first_name'] = array(
					'name'  => 'first_name',
					'id'    => 'first_name',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('first_name'),
					'class' => 'form-control',
					'placeholder' => 'Firstname'
				);
				$this->data['last_name'] = array(
					'name'  => 'last_name',
					'id'    => 'last_name',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('last_name'),
					'class' => 'form-control',
					'placeholder' => 'Lastname'
				);
				$this->data['email'] = array(
					'name'  => 'email',
					'id'    => 'email',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('email'),
					'class' => 'form-control',
					'placeholder' => 'Email Address'
				);
				$this->data['company'] = array(
					'name'  => 'company',
					'id'    => 'company',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('company'),
					'class' => 'form-control',
					'placeholder' => 'Address'
				);
				$this->data['phone'] = array(
					'name'  => 'phone',
					'id'    => 'phone',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('phone'),
					'class' => 'form-control',
					'placeholder' => 'Contact no.'

				);
				$this->data['password'] = array(
					'name'  => 'password',
					'id'    => 'password',
					'type'  => 'password',
					'value' => $this->form_validation->set_value('password'),
					'class' => 'form-control',
					'placeholder' => 'password',
				);
				$this->data['password_confirm'] = array(
					'name'  => 'password_confirm',
					'id'    => 'password_confirm',
					'type'  => 'password',
					'value' => $this->form_validation->set_value('password_confirm'),
					'class' => 'form-control',
					'placeholder' => 'Confirm Password'
				);

				$this->_render_page('auth/users', $this->data);
			}
		}
	}

	//log the user out
	function logout()
	{
		$this->data['title'] = "Logout";

		//log the user out
		$logout = $this->ion_auth->logout();

		//redirect them to the login page
		$this->session->set_flashdata('message', $this->ion_auth->messages());
		redirect('auth/login', 'refresh');
	}

	function remote()
	{

		if (!$this->ion_auth->logged_in()) {

			//redirect them to the login page
			redirect('auth/login', 'refresh');
		} else {

			$valid = true;

			if (isset($_POST['email'])) {
				$email = $_POST['email'];

				$users = $this->ion_auth->users()->result();

				foreach ($users as $details) {
					if ($email == $details->email) {
						$valid = false;
						break;
					}
				}
			} elseif (isset($_POST['group_name'])) {

				$group = $_POST['group_name'];

				$groups = $this->ion_auth->groups()->result();

				foreach ($groups as $details) {
					if ($group == $details->name) {
						$valid = false;
						break;
					}
				}
			}

			echo json_encode(array(
				'valid' => $valid,
			));
		}
	}

	//change password
	function change_password()
	{
		$this->form_validation->set_rules('old', $this->lang->line('change_password_validation_old_password_label'), 'required');
		$this->form_validation->set_rules('new', $this->lang->line('change_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[new_confirm]');
		$this->form_validation->set_rules('new_confirm', $this->lang->line('change_password_validation_new_password_confirm_label'), 'required');

		if (!$this->ion_auth->logged_in()) {
			redirect('auth/login', 'refresh');
		}

		$user = $this->ion_auth->user()->row();

		if ($this->form_validation->run() == false) {
			//display the form
			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

			$this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
			$this->data['old_password'] = array(
				'name' => 'old',
				'id'   => 'old',
				'type' => 'password',
			);
			$this->data['new_password'] = array(
				'name' => 'new',
				'id'   => 'new',
				'type' => 'password',
				'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
			);
			$this->data['new_password_confirm'] = array(
				'name' => 'new_confirm',
				'id'   => 'new_confirm',
				'type' => 'password',
				'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
			);
			$this->data['user_id'] = array(
				'name'  => 'user_id',
				'id'    => 'user_id',
				'type'  => 'hidden',
				'value' => $user->id,
			);

			//render
			$this->_render_page('auth/change_password', $this->data);
		} else {
			$identity = $this->session->userdata('identity');

			$change = $this->ion_auth->change_password($identity, $this->input->post('old'), $this->input->post('new'));

			if ($change) {
				//if the password was successfully changed
				$this->session->set_flashdata('message', $this->ion_auth->messages());
				$this->logout();
			} else {
				$this->session->set_flashdata('message', $this->ion_auth->errors());
				redirect('auth/change_password', 'refresh');
			}
		}
	}

	//forgot password
	function forgot_password()
	{
		//setting validation rules by checking wheather identity is username or email
		if ($this->config->item('identity', 'ion_auth') == 'username') {
			$this->form_validation->set_rules('email', $this->lang->line('forgot_password_username_identity_label'), 'required');
		} else {
			$this->form_validation->set_rules('email', $this->lang->line('forgot_password_validation_email_label'), 'required|valid_email');
		}


		if ($this->form_validation->run() == false) {

			//setup the form
			$this->data['attributes'] = array('class' => 'lockscreen-credentials');

			//setup the input
			$this->data['email'] = array(
				'name' => 'email',
				'id' 			 => 'email',
				'placeholder' 	 => 'Email Address',
				'class'			 => 'form-control'
			);

			if ($this->config->item('identity', 'ion_auth') == 'username') {
				$this->data['identity_label'] = $this->lang->line('forgot_password_username_identity_label');
			} else {
				$this->data['identity_label'] = $this->lang->line('forgot_password_email_identity_label');
			}

			//set any errors and display the form
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
			$this->_render_page('auth/forgot_password', $this->data);
		} else {
			// get identity from username or email
			if ($this->config->item('identity', 'ion_auth') == 'username') {
				$identity = $this->ion_auth->where('username', strtolower($this->input->post('email')))->users()->row();
			} else {
				$identity = $this->ion_auth->where('email', strtolower($this->input->post('email')))->users()->row();
			}
			if (empty($identity)) {

				if ($this->config->item('identity', 'ion_auth') == 'username') {
					$this->ion_auth->set_message('forgot_password_username_not_found');
				} else {
					$this->ion_auth->set_message('forgot_password_email_not_found');
				}

				$this->session->set_flashdata('message', $this->ion_auth->messages());
				redirect("auth/forgot_password", 'refresh');
			}

			//run the forgotten password method to email an activation code to the user
			$forgotten = $this->ion_auth->forgotten_password($identity->{$this->config->item('identity', 'ion_auth')});

			if ($forgotten) {
				//if there were no errors
				$this->session->set_flashdata('message', $this->ion_auth->messages());
				redirect("auth/login", 'refresh'); //we should display a confirmation page here instead of the login page
			} else {
				$this->session->set_flashdata('message', $this->ion_auth->errors());
				redirect("auth/forgot_password", 'refresh');
			}
		}
	}

	//reset password - final step for forgotten password
	public function reset_password($code = NULL)
	{
		if (!$code) {
			show_404();
		}

		$user = $this->ion_auth->forgotten_password_check($code);

		if ($user) {
			//if the code is valid then display the password reset form

			$this->form_validation->set_rules('new', $this->lang->line('reset_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[new_confirm]');
			$this->form_validation->set_rules('new_confirm', $this->lang->line('reset_password_validation_new_password_confirm_label'), 'required');

			if ($this->form_validation->run() == false) {
				//display the form

				//set the flash data error message if there is one
				$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

				$this->data['min_password_length'] = $this->config->item('min_password_length', 'ion_auth');
				$this->data['new_password'] = array(
					'name' => 'new',
					'id'   => 'new',
					'type' => 'password',
					'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
				);
				$this->data['new_password_confirm'] = array(
					'name' => 'new_confirm',
					'id'   => 'new_confirm',
					'type' => 'password',
					'pattern' => '^.{' . $this->data['min_password_length'] . '}.*$',
				);
				$this->data['user_id'] = array(
					'name'  => 'user_id',
					'id'    => 'user_id',
					'type'  => 'hidden',
					'value' => $user->id,
				);
				$this->data['csrf'] = $this->_get_csrf_nonce();
				$this->data['code'] = $code;

				//render
				$this->_render_page('auth/reset_password', $this->data);
			} else {
				// do we have a valid request?
				// if ($this->_valid_csrf_nonce() === FALSE || $user->id != $this->input->post('user_id'))
				// {

				// 	//something fishy might be up
				// 	$this->ion_auth->clear_forgotten_password_code($code);

				// 	show_error($this->lang->line('error_csrf'));

				// }
				// else
				// {
				// finally change the password
				$identity = $user->{$this->config->item('identity', 'ion_auth')};

				$change = $this->ion_auth->reset_password($identity, $this->input->post('new'));

				if ($change) {
					//if the password was successfully changed
					$this->session->set_flashdata('message', $this->ion_auth->messages());
					redirect("auth/login", 'refresh');
				} else {
					$this->session->set_flashdata('message', $this->ion_auth->errors());
					redirect('auth/reset_password/' . $code, 'refresh');
				}
				// }
			}
		} else {
			//if the code is invalid then send them back to the forgot password page
			$this->session->set_flashdata('message', $this->ion_auth->errors());
			redirect("auth/forgot_password", 'refresh');
		}
	}


	//activate the user
	function activate($id, $code = false)
	{
		if ($code !== false) {
			$activation = $this->ion_auth->activate($id, $code);
		} else if ($this->ion_auth->is_admin()) {
			$activation = $this->ion_auth->activate($id);
		}

		if ($activation) {
			//redirect them to the auth page
			$this->session->set_flashdata('message', $this->ion_auth->messages());
			redirect("auth/users", 'refresh');
		} else {
			//redirect them to the forgot password page
			$this->session->set_flashdata('message', $this->ion_auth->errors());
			redirect("auth/forgot_password", 'refresh');
		}
	}

	//deactivate the user
	function deactivate($id = NULL)
	{
		if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
			//redirect them to the home page because they must be an administrator to view this
			return show_error('You must be an administrator to view this page.');
		}

		$id = (int) $id;

		$this->load->library('form_validation');
		$this->form_validation->set_rules('confirm', $this->lang->line('deactivate_validation_confirm_label'), 'required');
		$this->form_validation->set_rules('id', $this->lang->line('deactivate_validation_user_id_label'), 'required|alpha_numeric');

		if ($this->form_validation->run() == FALSE) {
			// insert csrf check
			$this->data['csrf'] = $this->_get_csrf_nonce();
			$this->data['user'] = $this->ion_auth->user($id)->row();

			$this->_render_page('auth/deactivate_user', $this->data);
		} else {
			// do we really want to deactivate?
			if ($this->input->post('confirm') == 'yes') {
				// do we have a valid request?
				if ($this->_valid_csrf_nonce() === FALSE || $id != $this->input->post('id')) {
					show_error($this->lang->line('error_csrf'));
				}

				// do we have the right userlevel?
				if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin()) {
					$this->ion_auth->deactivate($id);
				}
			}

			//redirect them back to the auth page
			redirect('auth/users', 'refresh');
		}
	}

	//create a new user
	function create_user()
	{
		$this->data['title'] = "Create User";

		// if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin())
		// {
		// 	redirect('auth', 'refresh');
		// }

		$tables = $this->config->item('tables', 'ion_auth');

		//validate form input
		$this->form_validation->set_rules('first_name', $this->lang->line('create_user_validation_fname_label'), 'required');
		$this->form_validation->set_rules('last_name', $this->lang->line('create_user_validation_lname_label'), 'required');
		$this->form_validation->set_rules('email', $this->lang->line('create_user_validation_email_label'), 'required|valid_email|is_unique[' . $tables['users'] . '.email]');
		$this->form_validation->set_rules('phone', $this->lang->line('create_user_validation_phone_label'), 'required');
		$this->form_validation->set_rules('company', $this->lang->line('create_user_validation_company_label'), 'required');
		$this->form_validation->set_rules('password', $this->lang->line('create_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[password_confirm]');
		$this->form_validation->set_rules('password_confirm', $this->lang->line('create_user_validation_password_confirm_label'), 'required');

		if ($this->form_validation->run() == true) {
			$username = strtolower($this->input->post('first_name')) . ' ' . strtolower($this->input->post('last_name'));
			$email    = strtolower($this->input->post('email'));
			$password = $this->input->post('password');

			$additional_data = array(
				'first_name' => $this->input->post('first_name'),
				'last_name'  => $this->input->post('last_name'),
				'company'    => $this->input->post('company'),
				'phone'      => $this->input->post('phone'),
			);
		}
		if ($this->form_validation->run() == true && $this->ion_auth->register($username, $password, $email, $additional_data)) {
			//check to see if we are creating the user
			//redirect them back to the admin page
			$this->session->set_flashdata('message', $this->ion_auth->messages());
			redirect("auth", 'refresh');
		} else {
			//display the create user form
			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

			$this->data['first_name'] = array(
				'name'  => 'first_name',
				'id'    => 'first_name',
				'type'  => 'text',
				'value' => $this->form_validation->set_value('first_name'),
			);
			$this->data['last_name'] = array(
				'name'  => 'last_name',
				'id'    => 'last_name',
				'type'  => 'text',
				'value' => $this->form_validation->set_value('last_name'),
			);
			$this->data['email'] = array(
				'name'  => 'email',
				'id'    => 'email',
				'type'  => 'text',
				'value' => $this->form_validation->set_value('email'),
			);
			$this->data['company'] = array(
				'name'  => 'company',
				'id'    => 'company',
				'type'  => 'text',
				'value' => $this->form_validation->set_value('company'),
			);
			$this->data['phone'] = array(
				'name'  => 'phone',
				'id'    => 'phone',
				'type'  => 'text',
				'value' => $this->form_validation->set_value('phone'),
			);
			$this->data['password'] = array(
				'name'  => 'password',
				'id'    => 'password',
				'type'  => 'password',
				'value' => $this->form_validation->set_value('password'),
			);
			$this->data['password_confirm'] = array(
				'name'  => 'password_confirm',
				'id'    => 'password_confirm',
				'type'  => 'password',
				'value' => $this->form_validation->set_value('password_confirm'),
			);

			$this->_render_page('auth/create_user', $this->data);
		}
	}

	//edit a user
	function edit_user($id)
	{

		if ($this->ion_auth->logged_in()) {
			$this->load->view('header');
			$this->load->view('footer');
		}

		$this->data['title'] = "Edit User";

		if (!$this->ion_auth->logged_in() || (!$this->ion_auth->is_admin() && !($this->ion_auth->user()->row()->id == $id))) {
			redirect('auth', 'refresh');
		}

		$userDetails = $this->ion_auth->user($id)->row();
		$groups = $this->ion_auth->groups()->result_array();
		$currentGroups = $this->ion_auth->get_users_groups($id)->result();

		//get user details
		$this->data['SessionUser'] = $this->ion_auth->user()->row();

		//list the users
		$this->data['users'] = $this->ion_auth->users()->result();
		foreach ($this->data['users'] as $k => $user) {
			$this->data['users'][$k]->groups = $this->ion_auth->get_users_groups($user->id)->result();
		}

		//validate form input
		$this->form_validation->set_rules('first_name', $this->lang->line('edit_user_validation_fname_label'), 'required');
		$this->form_validation->set_rules('last_name', $this->lang->line('edit_user_validation_lname_label'), 'required');
		$this->form_validation->set_rules('phone', $this->lang->line('edit_user_validation_phone_label'), 'required');
		$this->form_validation->set_rules('company', $this->lang->line('edit_user_validation_company_label'), 'required');

		if (isset($_POST) && !empty($_POST)) {
			// do we have a valid request?
			if ($this->_valid_csrf_nonce() === FALSE || $id != $this->input->post('id')) {
				show_error($this->lang->line('error_csrf'));
			}

			//update the password if it was posted
			if ($this->input->post('password')) {
				$this->form_validation->set_rules('password', $this->lang->line('edit_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'ion_auth') . ']|max_length[' . $this->config->item('max_password_length', 'ion_auth') . ']|matches[password_confirm]');
				$this->form_validation->set_rules('password_confirm', $this->lang->line('edit_user_validation_password_confirm_label'), 'required');
			}

			if ($this->form_validation->run() === TRUE) {
				$data = array(
					'first_name' => $this->input->post('first_name'),
					'last_name'  => $this->input->post('last_name'),
					'company'    => $this->input->post('company'),
					'phone'      => $this->input->post('phone'),
				);

				//update the password if it was posted
				if ($this->input->post('password')) {
					$data['password'] = $this->input->post('password');
				}


				// Only allow updating groups if user is admin
				if ($this->ion_auth->is_admin()) {
					//Update the groups user belongs to
					$groupData = $this->input->post('groups');

					if (isset($groupData) && !empty($groupData)) {

						$this->ion_auth->remove_from_group('', $id);

						foreach ($groupData as $grp) {
							$this->ion_auth->add_to_group($grp, $id);
						}
					}
				}


				//check to see if we are updating the user
				if ($this->ion_auth->update($userDetails->id, $data)) {
					//redirect them back to the admin page if admin, or to the base url if non admin
					$this->session->set_flashdata('message', $this->ion_auth->messages());
					if ($this->ion_auth->is_admin()) {
						redirect(uri_string(), 'refresh');
					} else {
						redirect('/', 'refresh');
					}
				} else {
					//redirect them back to the admin page if admin, or to the base url if non admin
					$this->session->set_flashdata('message', $this->ion_auth->errors());
					if ($this->ion_auth->is_admin()) {
						redirect('auth', 'refresh');
					} else {
						redirect('/', 'refresh');
					}
				}
			}
		}

		//display the edit user form
		$this->data['csrf'] = $this->_get_csrf_nonce();

		//set the flash data error message if there is one
		$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

		//pass the user to the view
		$this->data['editUser'] = $userDetails;
		$this->data['groups'] = $groups;
		$this->data['currentGroups'] = $currentGroups;

		$this->data['first_name'] = array(
			'name'  => 'first_name',
			'id'    => 'first_name',
			'type'  => 'text',
			'value' => $this->form_validation->set_value('first_name', $userDetails->first_name),
			'class' => 'form-control',
			'placeholder' => 'Firstname'
		);
		$this->data['last_name'] = array(
			'name'  => 'last_name',
			'id'    => 'last_name',
			'type'  => 'text',
			'value' => $this->form_validation->set_value('last_name', $userDetails->last_name),
			'class' => 'form-control',
			'placeholder' => 'Lastname'
		);
		$this->data['company'] = array(
			'name'  => 'company',
			'id'    => 'company',
			'type'  => 'text',
			'value' => $this->form_validation->set_value('company', $userDetails->company),
			'class' => 'form-control',
			'placeholder' => 'Address'
		);
		$this->data['phone'] = array(
			'name'  => 'phone',
			'id'    => 'phone',
			'type'  => 'text',
			'value' => $this->form_validation->set_value('phone', $userDetails->phone),
			'class' => 'form-control',
			'placeholder' => 'Contact no'
		);
		$this->data['password'] = array(
			'name' => 'password',
			'id'   => 'password',
			'type' => 'password',
			'class' => 'form-control',
			'placeholder' => 'Password'
		);
		$this->data['password_confirm'] = array(
			'name' => 'password_confirm',
			'id'   => 'password_confirm',
			'type' => 'password',
			'class' => 'form-control',
			'placeholder' => 'Confirm password'
		);

		$this->_render_page('auth/edit_user', $this->data);
	}

	// create a new group
	function create_group()
	{
		$this->data['title'] = $this->lang->line('create_group_title');

		if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
			redirect('auth', 'refresh');
		}

		//validate form input
		$this->form_validation->set_rules('group_name', $this->lang->line('create_group_validation_name_label'), 'required|alpha_dash');

		if ($this->form_validation->run() == TRUE) {
			$new_group_id = $this->ion_auth->create_group($this->input->post('group_name'), $this->input->post('description'));
			if ($new_group_id) {
				// check to see if we are creating the group
				// redirect them back to the admin page
				$this->session->set_flashdata('message', $this->ion_auth->messages());
				redirect("auth", 'refresh');
			}
		} else {
			//display the create group form
			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

			$this->data['group_name'] = array(
				'name'  => 'group_name',
				'id'    => 'group_name',
				'type'  => 'text',
				'value' => $this->form_validation->set_value('group_name'),
			);
			$this->data['description'] = array(
				'name'  => 'description',
				'id'    => 'description',
				'type'  => 'text',
				'value' => $this->form_validation->set_value('description'),
			);

			$this->_render_page('auth/create_group', $this->data);
		}
	}

	//edit a group
	function edit_group($id)
	{
		// bail if no group id given
		if (!$id || empty($id)) {
			redirect('auth', 'refresh');
		}

		$this->data['title'] = $this->lang->line('edit_group_title');

		if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
			redirect('auth', 'refresh');
		}

		$group = $this->ion_auth->group($id)->row();

		//validate form input
		$this->form_validation->set_rules('group_name', $this->lang->line('edit_group_validation_name_label'), 'required|alpha_dash');

		if (isset($_POST) && !empty($_POST)) {
			if ($this->form_validation->run() === TRUE) {
				$group_update = $this->ion_auth->update_group($id, $_POST['group_name'], $_POST['group_description']);

				if ($group_update) {
					$this->session->set_flashdata('message', $this->lang->line('edit_group_saved'));
				} else {
					$this->session->set_flashdata('message', $this->ion_auth->errors());
				}
				redirect("auth", 'refresh');
			}
		}

		//set the flash data error message if there is one
		$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

		//pass the user to the view
		$this->data['group'] = $group;

		$readonly = $this->config->item('admin_group', 'ion_auth') === $group->name ? 'readonly' : '';

		$this->data['group_name'] = array(
			'name'  => 'group_name',
			'id'    => 'group_name',
			'type'  => 'text',
			'value' => $this->form_validation->set_value('group_name', $group->name),
			$readonly => $readonly,
		);
		$this->data['group_description'] = array(
			'name'  => 'group_description',
			'id'    => 'group_description',
			'type'  => 'text',
			'value' => $this->form_validation->set_value('group_description', $group->description),
		);

		$this->_render_page('auth/edit_group', $this->data);
	}


	function _get_csrf_nonce()
	{
		$this->load->helper('string');
		$key   = random_string('alnum', 8);
		$value = random_string('alnum', 20);
		$this->session->set_flashdata('csrfkey', $key);
		$this->session->set_flashdata('csrfvalue', $value);

		return array($key => $value);
	}

	function _valid_csrf_nonce()
	{
		var_dump($this->input->post($this->session->flashdata('csrfkey')));
		die;
		if (
			$this->input->post($this->session->flashdata('csrfkey')) != FALSE &&
			$this->input->post($this->session->flashdata('csrfkey')) == $this->session->flashdata('csrfvalue')
		) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	function _render_page($view, $data = null, $render = false)
	{

		$this->viewdata = (empty($data)) ? $this->data : $data;

		$view_html = $this->load->view($view, $this->viewdata, $render);

		if (!$render) return $view_html;
	}

	public function verifyCode()
	{

		$verifyCode = $this->input->post('verifyCode');
		$email = $_POST['email'];

		$this->db->where('email', $email);
		$this->db->where('verifyCode', $verifyCode);
		$user = $this->db->get('users');
		if ($user->num_rows() == 0) {
			redirect('auth/login', 'refresh');
			}
		else {
			redirect('/', 'refresh');
		}
	}
}
