<?php

if(!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class ChatbotConnection extends Module implements WidgetInterface {

	const CHATBOT_TPL_FILE = 'chatbot.tpl';

    const BTN_ADD_CHATBOT = 'addChatBot';

    public function __construct() {
        $this->name = "chatbotconnection";
        $this->tab = "front_office_features";
        $this->version = "1.0.0";
        $this->author = "Mateusz Czosnyka";
        $this->need_instance = 0;
        $this->ps_version_compliancy = [
            "min" => "1.7",
            "max" => _PS_VERSION_
        ];
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l("Chatbot connection.");
        $this->description = $this->l("Adds chatbot to your website.");
        $this->confirmUninstall = $this->l("Are you sure?");
    }

    public function install(): Bool {
        return parent::install() && 
			$this->registerHook('displayFooter');
    }

    public function uninstall(): Bool {
        return parent::uninstall();
    }

    public function renderWidget($hookName, array $configuration) {
        $cacheId = $this->name . '|' . $hookName . '|' . $this->context->shop->id . '|' . $this->context->language->id;
        if (!$this->isCached(self::CHATBOT_TPL_FILE, $cacheId)) {
            $this->context->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }
        return $this->display(__FILE__, self::CHATBOT_TPL_FILE, $cacheId);
    }

	public function getWidgetVariables($hookName, array $configuration) {
        return [
            'botPressId' => Configuration::get('BOT_PRESS_ID'),
        ];
    }
  
    public function getContent() {
		$html = '';
        $error = '';
		try {
			$html .= $this->postProcess();
		} catch (Exception $e) {
            $error .= $this->displayError($e->getMessage());
        }
		try {
            $html .= $this->renderForm();
        } catch (Exception $e) {
            $error .= $this->displayError($e->getMessage());
        }
        return $error . $html;
    }

	public function postProcess() {
		if (Tools::isSubmit(self::BTN_ADD_CHATBOT)) {
			$this->saveChatbotData();
			$this->checkConnection();
			return $this->displayConfirmation($this->l('Success'));
		}
		return '';
	}

	public function saveChatbotData() {
		Configuration::updateValue('BOT_PRESS_ID', Tools::getValue("botPressId"));
        Configuration::updateValue('BOT_PRESS_API_KEY', Tools::getValue("apiKey"));
		return true;
	}
  
  	public function checkConnection() {
    	$url = "https://documents.botpress.cloud/api/" . Configuration::get('BOT_PRESS_ID') . "/documents";
      	$curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
        $headers = [
            "Authorization: " . Configuration::get('BOT_PRESS_API_KEY')
        ];
      	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      	$response = curl_exec($curl);
		$httpCode;
      	if ($response === false) {
    		throw new Exception($this->l('Error! Status code 500.'));
		} else {
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        }
      	curl_close($curl);
      	if (intval($httpCode / 100) != 2) {
    		throw new Exception($this->l('Error! Status code ') . $httpCode . '.');
		}
      	return true;
    }

	public function renderForm() {
		$fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Chatbot Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('API Key'),
                        'name' => 'apiKey',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Chatbot Id'),
                        'name' => 'botPressId',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-success pull-right',
                ],
            ],
        ];
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = self::BTN_ADD_CHATBOT;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        return $helper->generateForm([$fields_form]);
	}
}
