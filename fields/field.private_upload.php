<?php

	/**
	 * @package toolkit
	 */
	/**
	 * A simple Upload field that essentially maps to HTML's `<input type='file '/>`.
	 */

	require_once(TOOLKIT.'/fields/field.upload.php');

	Class fieldPrivate_upload extends fieldUpload {

		protected static $imageMimeTypes = array(
			'image/gif',
			'image/jpg',
			'image/jpeg',
			'image/pjpeg',
			'image/png',
		);

		/**
		 * Constructor
		 */
		public function __construct(){
			parent::__construct();

			$this->_name = __('Private File Upload');
			$this->_required = true;

			$this->set('location', 'sidebar');
			$this->set('required', 'no');
		}

		/**
		 * Display the publish panel
		 * @param \XMLElement $wrapper
		 * @param null $data
		 * @param null $flagWithError
		 * @param null $fieldnamePrefix
		 * @param null $fieldnamePostfix
		 */
		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){

			if(!is_dir($this->get('destination') . '/')){
				$flagWithError = __('The destination directory, <code>%s</code>, does not exist.', array($this->get('destination')));
			}

			elseif(!$flagWithError && !is_writable($this->get('destination') . '/')){
				$flagWithError = __('Destination folder, <code>%s</code>, is not writable. Please check permissions.', array($this->get('destination')));
			}

			$label = Widget::Label($this->get('label'));
			$class = 'file';
			$label->setAttribute('class', $class);
			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', __('Optional')));

			$span = new XMLElement('span', NULL, array('class' => 'frame'));

			if($data['file'])
            {
                $span->appendChild(Widget::Anchor(basename($data['file']), URL.'/symphony/extension/private_upload/?file='.$data['file']));
            }

			$span->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, $data['file'], ($data['file'] ? 'hidden' : 'file')));

			$label->appendChild($span);

			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);

		}

		/**
		 * Run actions when the entry is deleted.
		 * @param array|int $entry_id
		 * @param null $data
		 * @return bool
		 */
/*		public function entryDataCleanup($entry_id, $data){
			$file_location = '/'.ltrim($data['file'], '/');

			if($file_location != '/' && is_file($file_location)){
				General::deleteFile($file_location);
			}

			parent::entryDataCleanup($entry_id);

			return true;
		}*/

		/**
		 * Check the fields for errors
		 * @param array $errors
		 * @param bool $checkForDuplicates
		 */
		public function checkFields(&$errors, $checkForDuplicates=true){

			if(!is_dir($this->get('destination') . '/')){
				$errors['destination'] = __('Directory <code>%s</code> does not exist.', array($this->get('destination')));
			}

			elseif(!is_writable($this->get('destination') . '/')){
				$errors['destination'] = __('Destination folder, <code>%s</code>, is not writable. Please check permissions.', array($this->get('destination')));
			}

			parent::checkFields($errors, $checkForDuplicates);
		}

		/**
		 * Prepare the table value for the index screen
		 * @param array $data
		 * @param null|XMLElement $link
		 * @return string
		 */
		public function prepareTableValue($data, XMLElement $link=NULL){
			if(!$file = $data['file']){
				if($link) return parent::prepareTableValue(null, $link);
				else return parent::prepareTableValue(null);
			}

            $linkStr = '<a href="'.URL.'/symphony/extension/private_upload/?file='.$file.'">'.basename($file).'</a>';
            return $linkStr;
		}

		/**
		 * Append the formatted element to the XML output
		 * @param XMLElement $wrapper
		 * @param array $data
		 * @param bool $encode
		 * @param null $mode
		 * @param null $entry_id
		 */
		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null){
			// It is possible an array of NULL data will be passed in. Check for this.
			if(!is_array($data) || !isset($data['file']) || is_null($data['file'])){
				return;
			}

			$item = new XMLElement($this->get('element_name'));
			$file = $data['file'];
			$item->setAttributeArray(array(
				'size' => (file_exists($file) && is_readable($file) ? General::formatFilesize(filesize($file)) : 'unknown'),
			 	'path' => dirname($data['file']),
				'type' => $data['mimetype'],
			));

			$item->appendChild(new XMLElement('filename', General::sanitize(basename($data['file']))));

			$m = unserialize($data['meta']);

			if(is_array($m) && !empty($m)){
				$item->appendChild(new XMLElement('meta', NULL, $m));
			}

			$wrapper->appendChild($item);
		}

		/**
		 * Display the settings panel when editing a section
		 * @param \XMLElement $wrapper
		 * @param null $errors
		 */
		public function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			$label = Widget::Label(__('Destination directory on the server'));

            $destination = $this->get('destination');
            if($destination == null)
            {
                $destination = DOCROOT;
            }
            $label->appendChild(Widget::Input('fields['.$this->get('sortorder').'][destination]', $destination));

			if(isset($errors['destination'])) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $errors['destination']));
			else $wrapper->appendChild($label);

			$this->buildValidationSelect($wrapper, $this->get('validator'), 'fields['.$this->get('sortorder').'][validator]', 'upload');

			$div = new XMLElement('div', NULL, array('class' => 'compact'));
			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);
			$wrapper->appendChild($div);

		}

		/**
		 * Check the POST field data
		 * @param array $data
		 * @param string $message
		 * @param null $entry_id
		 * @return int
		 */
		function checkPostFieldData($data, &$message, $entry_id=NULL){

			/*
				UPLOAD_ERR_OK
				Value: 0; There is no error, the file uploaded with success.

				UPLOAD_ERR_INI_SIZE
				Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.

				UPLOAD_ERR_FORM_SIZE
				Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.

				UPLOAD_ERR_PARTIAL
				Value: 3; The uploaded file was only partially uploaded.

				UPLOAD_ERR_NO_FILE
				Value: 4; No file was uploaded.

				UPLOAD_ERR_NO_TMP_DIR
				Value: 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.

				UPLOAD_ERR_CANT_WRITE
				Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0.

				UPLOAD_ERR_EXTENSION
				Value: 8; File upload stopped by extension. Introduced in PHP 5.2.0.

				Array
				(
					[name] => filename.pdf
					[type] => application/pdf
					[tmp_name] => /tmp/php/phpYtdlCl
					[error] => 0
					[size] => 16214
				)
			*/
			$message = NULL;

			if(empty($data) || $data['error'] == UPLOAD_ERR_NO_FILE) {

				if($this->get('required') == 'yes'){
					$message = __("'%s' is a required field.", array($this->get('label')));
					return self::__MISSING_FIELDS__;
				}

				return self::__OK__;
			}

			// Its not an array, so just retain the current data and return
			if(!is_array($data)){
				// Ensure the file exists in the `WORKSPACE` directory
				// @link http://symphony-cms.com/discuss/issues/view/610/
				$file = preg_replace(array('%/+%', '%(^|/)../%'), '/', $data);

				if(!file_exists($file) || !is_readable($file)){
					$message = __('The file uploaded is no longer available. Please check that it exists, and is readable.');
					return self::__INVALID_FIELDS__;
				}

				// Ensure that the file still matches the validator and hasn't
				// changed since it was uploaded.
				if($this->get('validator') != NULL){
					$rule = $this->get('validator');

					if(!General::validateString($file, $rule)){
						$message = __("File chosen in '%s' does not match allowable file types for that field.", array($this->get('label')));
						return self::__INVALID_FIELDS__;
					}
				}

				return self::__OK__;
			}


			if(!is_dir($this->get('destination') . '/')){
				$message = __('The destination directory, <code>%s</code>, does not exist.', array($this->get('destination')));
				return self::__ERROR__;
			}

			elseif(!is_writable($this->get('destination') . '/')){
				$message = __('Destination folder, <code>%s</code>, is not writable. Please check permissions.', array($this->get('destination')));
				return self::__ERROR__;
			}

			if($data['error'] != UPLOAD_ERR_NO_FILE && $data['error'] != UPLOAD_ERR_OK){

				switch($data['error']){
					case UPLOAD_ERR_INI_SIZE:
						$message = __('File chosen in "%1$s" exceeds the maximum allowed upload size of %2$s specified by your host.', array($this->get('label'), (is_numeric(ini_get('upload_max_filesize')) ? General::formatFilesize(ini_get('upload_max_filesize')) : ini_get('upload_max_filesize'))));
						break;

					case UPLOAD_ERR_FORM_SIZE:
						$message = __('File chosen in "%1$s" exceeds the maximum allowed upload size of %2$s, specified by Symphony.', array($this->get('label'), General::formatFilesize(Symphony::Configuration()->get('max_upload_size', 'admin'))));
						break;

					case UPLOAD_ERR_PARTIAL:
					case UPLOAD_ERR_NO_TMP_DIR:
						$message = __("File chosen in '%s' was only partially uploaded due to an error.", array($this->get('label')));
						break;

					case UPLOAD_ERR_CANT_WRITE:
						$message = __("Uploading '%s' failed. Could not write temporary file to disk.", array($this->get('label')));
						break;

					case UPLOAD_ERR_EXTENSION:
						$message = __("Uploading '%s' failed. File upload stopped by extension.", array($this->get('label')));
						break;
				}

				return self::__ERROR_CUSTOM__;
			}


			// Sanitize the filename
			$data['name'] = Lang::createFilename($data['name']);

			if($this->get('validator') != NULL){
				$rule = $this->get('validator');

				if(!General::validateString($data['name'], $rule)){
					$message = __("File chosen in '%s' does not match allowable file types for that field.", array($this->get('label')));
					return self::__INVALID_FIELDS__;
				}

			}

			return self::__OK__;

		}

		/**
		 * Process the raw field data
		 * @param mixed $data
		 * @param int $status
		 * @param null $message
		 * @param bool $simulate
		 * @param null $entry_id
		 * @return array|mixed
		 */
		public function processRawFieldData($data, &$status, &$message=null, $simulate=false, $entry_id=null) {

			$status = self::__OK__;

			//fixes bug where files are deleted, but their database entries are not.
			if($data === NULL){
				return array(
					'file' => NULL,
					'mimetype' => NULL,
					'size' => NULL,
					'meta' => NULL
				);
			}

			// Its not an array, so just retain the current data and return
			if(!is_array($data)){

				$status = self::__OK__;

				// Ensure the file exists in the `WORKSPACE` directory
				// @link http://symphony-cms.com/discuss/issues/view/610/
				$file = preg_replace(array('%/+%', '%(^|/)\.\./%'), '/', $data);

				$result = array(
					'file' => $data,
					'mimetype' => NULL,
					'size' => NULL,
					'meta' => NULL
				);

				// Grab the existing entry data to preserve the MIME type and size information
				if(isset($entry_id) && !is_null($entry_id)){
					$row = Symphony::Database()->fetchRow(0, sprintf(
						"SELECT `file`, `mimetype`, `size`, `meta` FROM `tbl_entries_data_%d` WHERE `entry_id` = %d",
						$this->get('id'),
						$entry_id
					));
					if(!empty($row)){
						$result = $row;
					}
				}

				if(!file_exists($file) || !is_readable($file)){
					$message = __('The file uploaded is no longer available. Please check that it exists, and is readable.');
					$status = self::__INVALID_FIELDS__;
					return $result;
				}
				else{
					if(empty($result['mimetype'])) $result['mimetype'] = (function_exists('mime_content_type') ? mime_content_type($file) : 'application/octet-stream');
					if(empty($result['size'])) $result['size'] = filesize($file);
					if(empty($result['meta'])) $result['meta'] = serialize(self::getMetaInfo($file, $result['mimetype']));
				}

				return $result;
			}

			if($simulate && is_null($entry_id)) return $data;

			// Upload the new file
			$abs_path = '/' . trim($this->get('destination'), '/');
			$rel_path = str_replace('/workspace', '', $this->get('destination'));
			$existing_file = NULL;

			if(!is_null($entry_id)){
				$row = Symphony::Database()->fetchRow(0, sprintf(
					"SELECT * FROM `tbl_entries_data_%s` WHERE `entry_id` = %d LIMIT 1",
					$this->get('id'),
					$entry_id
				));

				$existing_file = $row['file'];

				// File was removed
				if($data['error'] == UPLOAD_ERR_NO_FILE && !is_null($existing_file) && is_file($existing_file)){
					General::deleteFile($existing_file);
				}
			}

			if($data['error'] == UPLOAD_ERR_NO_FILE || $data['error'] != UPLOAD_ERR_OK){
				return;
			}

			// Sanitize the filename
			$data['name'] = Lang::createFilename($data['name']);
            $filename = $data['name'];

            // Check for duplicates:
            $ok = !file_exists($abs_path.'/'.$filename);
            $i  = 2;
            while($ok == false)
            {
                $a = explode('.', $data['name']);
                $a[count($a)-2].='-'.$i;
                $filename = implode('.', $a);
                $new_file = $abs_path.'/'.$filename;
                $i++;
                $ok = !file_exists($new_file);
            }
            $data['name'] = $filename;
            
			if(!General::uploadFile($abs_path, $data['name'], $data['tmp_name'], Symphony::Configuration()->get('write_mode', 'file'))){

				$message = __('There was an error while trying to upload the file <code>%1$s</code> to the target directory <code>%2$s</code>.', array($data['name'], 'workspace/'.ltrim($rel_path, '/')));
				$status = self::__ERROR_CUSTOM__;
				return;
			}

			$status = self::__OK__;

			$file = rtrim($rel_path, '/') . '/' . trim($data['name'], '/');

			// File has been replaced
			if(!is_null($existing_file) && (strtolower($existing_file) != strtolower($file)) && is_file($existing_file)){
				General::deleteFile($existing_file);
			}

			// If browser doesn't send MIME type (e.g. .flv in Safari)
			if (strlen(trim($data['type'])) == 0){
				$data['type'] = (function_exists('mime_content_type') ? mime_content_type($file) : 'application/octet-stream');
			}

			return array(
				'file' => $file,
				'size' => $data['size'],
				'mimetype' => $data['type'],
				'meta' => serialize(self::getMetaInfo($file, $data['type']))
			);
		}

	}
