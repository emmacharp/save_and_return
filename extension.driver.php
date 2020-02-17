<?php

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	/*
	Copyright: Deux Huit Huit 2012-2016
	License: MIT
	*/
	class extension_save_and_return extends Extension {

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'appendJS'
				),
				array(
					'page' => '/backend/',
					'delegate' => 'AdminPagePreGenerate',
					'callback' => 'appendElement'
				),
				array(
					'page' => '/publish/edit/',
					'delegate' => 'EntryPostEdit',
					'callback' => 'entryPostEdit'
				),
				array(
					'page' => '/publish/new/',
					'delegate' => 'EntryPostCreate',
					'callback' => 'entryPostEdit'
				)
			);
		}

		public function entryPostEdit($context) {
			$section = $context['section'];
			$errors = Administration::instance()->Page->getErrors();

			$isReturn = isset($_POST['fields']['save-and-return-h']) && strlen($_POST['fields']['save-and-return-h']) > 1;
			$isNew = isset($_POST['fields']['save-and-new-h']) && strlen($_POST['fields']['save-and-new-h']) > 1;

			// if return or new button was hit
			if ($isReturn || $isNew) {
				try {
					redirect(vsprintf(
						$this->getPath($isNew),
						array(
							SYMPHONY_URL,
							$section->get('handle')
						)
					));
				} catch (Exception $e) {
					Symphony::initialiseLog();
					Symphony::Logs()->pushExceptionToLog($e, true, true, false);
				}
			}
		}

		public function appendElement($context) {

			// if in edit or new page
			if ($this->isInEditOrNew()) {

				// Get this section's limit
				$limits = $this->getSectionLimit();

				// Exit early if no limits where found
				if ($limits === false || empty($limits) || !is_array($limits)) {
					return;
				}

				// Exit early if the limit is one
				if ($limits['limit'] == 1) {
					return;
				}

				// add new if limit is 0 or total is less than limit
				$shouldAddNew = $limits['limit'] == 0 || ($limits['total']+1) < $limits['limit'];

				// add return if the limit is not 1
				$shouldAddReturn = $limits['limit'] != 1;

				$page = $context['oPage'];

				$controls = $page->Controls;

				$button_wrap = new XMLElement('div');
				$button_wrap->setAttribute('id', 'save-and');

				if ($shouldAddReturn) {
					// add return button in wrapper
					$button_return = $this->createButton('save-and-return', 'Save & return', '<svg height="15" width="15" class="icon"  fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" x="0px" y="0px"><path d="M4.08,9.38a1,1,0,0,0,.22.33l4,4A1,1,0,0,0,10,13V10h4a4,4,0,0,1,0,8H11a1,1,0,0,0,0,2h3A6,6,0,0,0,14,8H10V5a1,1,0,0,0-1.71-.71l-4,4a1,1,0,0,0-.22,1.09Z"></path></svg>');
					$hidden_return = $this->createHidden('save-and-return-h');

					$button_wrap->appendChild($button_return);
					$button_wrap->appendChild($hidden_return);
				}

				if ($shouldAddNew) {
					// add the new button
					$button_new = $this->createButton('save-and-new', 'Save & new', '<svg height="10" width="10" class="icon"  fill="currentColor" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve"><path d="M41.4,58.6l0.1,27.9c-0.1,4.7,3.8,8.6,8.5,8.5c4.8,0.1,8.6-3.8,8.6-8.5V58.6h27.8c4.7,0.1,8.6-3.8,8.6-8.6  c0-4.7-3.8-8.5-8.6-8.5H58.6V13.6C58.6,8.8,54.8,5,50,5c-4.7,0-8.6,3.8-8.5,8.6v27.9H13.6C8.8,41.5,5,45.3,5,50  c0,4.8,3.8,8.7,8.6,8.6H41.4z"></path></svg>');
					$hidden_new = $this->createHidden('save-and-new-h');

					$button_wrap->appendChild($button_new);
					$button_wrap->appendChild($hidden_new);
				}

				// save current query string: the raw (visible) query string
				$queryString = explode('?', $_SERVER['REQUEST_URI'], 2);
				if ($queryString != false && count($queryString) == 2) {
					$queryString = $queryString[1];
				} else {
					$queryString = '';
				}
				$qs_hidden = $this->createHidden('save-and-qs');
				$qs_hidden->setAttribute('value', $queryString);
				$button_wrap->appendChild($qs_hidden);

				// add content to the right div
				$div_action = $this->getChildrenWithClass($controls, 'div', 'actions');

				// if there is no fields, div_action may not be there
				if ($div_action != null) {
					$div_action->insertChildAt(1, $button_wrap);
				}
			}
		}

		private function createButton($id, $value, $icon) {
			$btn = new XMLElement('button', Widget::SVGIcon('save') . $icon, array(
				'id' => $id,
				'name' => 'action[save]',
				'title' => __($value),
				'type' => 'submit'
			));

			return $btn;
		}

		private function createHidden($id) {
			$h = new XMLElement('input', null, array(
				'id' => $id,
				'name' => "fields[$id]",
				'type' => 'hidden'
			));
			$h->setSelfClosingTag(true);

			return $h;
		}

		private function getPath($isNew) {

			$queryString = isset($_POST['fields']['save-and-qs']) && strlen($_POST['fields']['save-and-qs']) > 1;

			if ($queryString) {
				$queryString = '?' . urldecode($_POST['fields']['save-and-qs']);
			} else {
				$queryString = '';
			}

			if ($isNew) {
				return '%s/publish/%s/new/' . $queryString;
			}
			return '%s/publish/%s/' . $queryString;
		}

		private function isInEditOrNew() {
			$c = Administration::instance()->getPageCallback();
			$c = $c['context']['page'];

			return Symphony::Engine()->isLoggedIn() && ($c == 'edit' || $c == 'new');
		}

		private function getChildrenWithClass($rootElement, $tagName, $className) {
			if (! ($rootElement) instanceof XMLElement) {
				return null; // not and XMLElement
			}

			// contains the right css class and the right node name
			if (strpos($rootElement->getAttribute('class'), $className) > -1 && $rootElement->getName() == $tagName) {
				return $rootElement;
			}

			// recursive search in child elements
			foreach ($rootElement->getChildren() as $child) {
				$res = $this->getChildrenWithClass($child, $tagName, $className);

				if ($res != null) {
					return $res;
				}
			}

			return null;
		}


		private function getSectionLimit(){
			$extman = Symphony::ExtensionManager();

			// limit section entries
			$status = $extman->fetchStatus(array('handle' => 'limit_section_entries', 'version' => '1'));

			if (in_array(Extension::EXTENSION_ENABLED, $status)) {
				require_once (EXTENSIONS . '/limit_section_entries/lib/class.LSE.php');
				$limit = LSE::getMaxEntries();
				$total = LSE::getTotalEntries();

				return array(
					'limit' => $limit,
					'total' => $total
				);
			}

			return false;
		}

		public function appendJS($context){

			if ($this->isInEditOrNew()) {

				Administration::instance()->Page->addElementToHead(
					new XMLElement(
						'script',
						"(function($){
							$(function(){
								$('#save-and-return').click(function () {
									$('#save-and-return-h').val('true');
								});

								$('#save-and-new').click(function () {
									$('#save-and-new-h').val('true');
								});
							});
						})(jQuery);"
					), time()+100
				);

				Administration::instance()->Page->addStylesheetToHead(
					URL . '/extensions/save_and_return/assets/save_and_return.css',
					'screen',
					time() + 1,
					false
				);
			}

		}
	}

