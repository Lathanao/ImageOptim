<?php /********************************************************************//**
*          Beautiful Theme for Prestashop
*
*          @author         <tanguy.salmon@gmail.com>
*          @copyright      2017 Lathanao
*          @version        0.1.1
*          @license        Commercial license see README.md
******************************************************************************/

use ImageOpt\ImageOpt;
include_once(dirname(__FILE__).'/models/ImageOpt.php');

if (!defined('_TB_VERSION_'))  exit;


class ImageOptim extends Module
{
    protected $_html = '';
    private $values = array(      "IMAGEOPTIM_ACTIVE"     => "1",
                                  "IMAGEOPTIM_DEMO"       => "1",
                                  "IMAGEOPTIM_LOCAL"      => "1",
                                  "IMAGEOPTIM_QUALITY"    => "Doesn't work for now",
                                  "IMAGEOPTIM_URL"        => "http://api.resmush.it/ws.php?img=",
                                  "IMAGEOPTIM_QUALITY_V"  => "100");

    public function __construct()
    {
        $this->name = 'ImageOptim';
        $this->tab = 'front_office_features';
        $this->version = '0.1.1';
        $this->author = 'Lathanao';
        $this->bootstrap = true;
        $this->secure_key = Tools::encrypt($this->name);

        parent::__construct();

        $this->displayName = $this->l('Optimize your images');
        $this->description = $this->l('Optimize add lighten the weight your shop\'s images.');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
        if (!is_dir(_PS_PROD_IMG_DIR_))
            $this->warning = $this->l('Your images directory doesn\'t exist.');
        if (!is_writable(_PS_PROD_IMG_DIR_))
            $this->warning = $this->l('Your images directory isn\'t writable.');

    }

    public function install()
    {
        foreach ($this->values as $key => $value)
            if(!Configuration::updateValue($key, $value))
                return false;

        if (!ImageOpt::createDatabase())
            return false;

        return parent::install();
    }

    public function uninstall()
    {
        if (!ImageOpt::dropDatabase())
            return false;

        return parent::uninstall();
    }

    public function getContent()
    {
        $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/css/jquery.growl.css');
        $this->context->controller->addJS(_MODULE_DIR_.$this->name.'/js/admin_imageOptim.js');
        $this->context->controller->addJS(_MODULE_DIR_.$this->name.'/js/jquery.growl.js');

        $this->getSetup();
        $this->setSetup();

        $this->updateImagesOriginInDb();

        $this->_html .= $this->renderList();
        $this->_html .= $this->renderForm();
        return $this->_html;
    }

    public function renderForm()
    {
        $formFields = array(
            'form' => array(
                'legend' => array('title' => $this->l('Settings'),'icon' => 'icon-cogs'),
                'description' => $this->l('This module allows get optimsed image from a free service provide at https://resmush.it.').'<br /><br />'.
                                 $this->l('Great result are most important ond image obove 100ko. Under, the save rate is not really meaningful.').'<br /><br />'.
                                 $this->l('The default quality offert by the service is 92%, you can modify it with the field bellow"').'<br /><br />'.
                                 $this->l('You can try in demo mode before to test the service, and check in your image dir the picture with a surfixed name with "opt" like "1-cart_default_opt.jpg".').'<br /><br />'.
                                 $this->l('Original images are never optimised.').'<br /><br />'.
                                 $this->l('If something wrong happened, just rebuilt your picture in admin panel > preferences > images.').'<br /><br />'.
                                 $this->l('In admin panel > preferences > images, you can setup JPEG compression at 100%, and let this modules weightlight you final images.').'<br /><br />'.
                                 $this->l('Thank you for using this module.').'<br /><br />',
                'input'  => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Demo Mode'),
                        'name' => 'IMAGEOPTIM_DEMO',
                        'required' => false,
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Use image url in "front.thirtybees.com" for Local machin testing ?'),
                        'name' => 'IMAGEOPTIM_LOCAL',
                        'required' => false,
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type'  => 'text',
                        'label' => $this->l('URL Optimiser'),
                        'name'  => 'IMAGEOPTIM_URL',
                    ),
                    array(
                        'type'  => 'text',
                        'label' => $this->l('Optimisation Quality'),
                        'name'  => 'IMAGEOPTIM_QUALITY',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->default_form_language = $this->context->language->id;
        $helper->show_toolbar = true;
        $helper->submit_action = 'submitForm';
        $helper->tpl_vars = array(
            'fields_value' => $this->getSetup(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        );

        return $helper->generateForm(array($formFields));
    }

    public function renderList()
    {
      $fields_list = array(
        'id' => array(
          'title' => $this->l('Id'),
          'search' => true,
          'width' => 30,
        ),
        'id_image' => array(
          'title' => $this->l('Id image'),
          'search' => true,
          'width' => 50,
        ),
        'id_type' => array(
          'title' => $this->l('type'),
          'search' => true,
          'width' => 100,
        ),
        // 'width' => array(
        //   'title' => $this->l('width'),
        //   'search' => true,
        //   'width' => 40,
        // ),
        // 'height' => array(
        //   'title' => $this->l('height'),
        //   'search' => true,
        //   'width' => 40,
        // ),
        // 'md5_origin' => array(
        //   'title' => $this->l('Md5'),
        //   'search' => true,
        // ),
        'weight_origin' => array(
          'title' => $this->l('Weight before'),
        ),
        'weight_opt' => array(
          'title' => $this->l('Weight after'),
        ),
        'rate' => array(
          'title' => $this->l('Rate'),
          'search' => true,
          'width' => 100,
        ),
        'Quality' => array(
          'title' => $this->l('Quality'),
          'search' => true,
          'width' => 100,
        ),
      );

      $helper_list = New HelperList();
      $helper_list->module = $this;
      $helper_list->title = $this->l('Original Images list');
      $helper_list->shopLinkType = '';
      $helper_list->no_link = true;
      $helper_list->show_toolbar = true;
      $helper_list->simple_header = false;
      $helper_list->identifier = 'id';
      $helper_list->table = 'merged';
      $helper_list->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name;
      $helper_list->token = Tools::getAdminTokenLite('AdminModules');

      $this->_helperlist = $helper_list;

      $listImages = $this->getListImageOpt();
      $helper_list->listTotal = count($listImages);

      /* Paginate the result */
      $page = ($page = Tools::getValue('submitFilter'.$helper_list->table)) ? $page : 1;
      $pagination = ($pagination = Tools::getValue($helper_list->table.'_pagination')) ? $pagination : 50;
      $listImages = $this->paginateSubscribers($listImages, $page, $pagination);

      $helper_list->actions = array('Generate');
      $helper_list->bulk_actions = [
          'generateBulkImages' => ['text' => $this->l('Generate Bulk Images'), 'icon' => 'icon-refresh'],
      ];

      return $helper_list->generateList($listImages, $fields_list);
    }

    public function getListImageOpt()
    {
        $dbquery = new DbQuery();
        $dbquery->select('iop.id, iop.id_image, iop.id_type, iop.width, iop.height, iop.weight_origin, iop.weight_opt,  (iop.weight_opt / iop.weight_origin * 100) as rate, iop.quality, iop.md5_origin, iop.date_upd');
        $dbquery->from('image_opt', 'iop');

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($dbquery->build());
    }

    public function getListImageOrigin()
    {
        $dbquery = new DbQuery();
        $dbquery->select('*, img.`id_image` AS `id`');
        $dbquery->from('image_origin', 'img');
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($dbquery->build());
    }

    public function paginateSubscribers($subscribers, $page = 1, $pagination = 50)
    {
        if(count($subscribers) > $pagination)
          $subscribers = array_slice($subscribers, $pagination * ($page - 1), $pagination);

        return $subscribers;
    }

    public function displayGenerateLink($token = null, $id, $name = null)
    {
        $imageOpt = new ImageOpt($id);

        $this->smarty->assign(array( 'href' => $this->context->link->getAdminLink('AdminModules', true/*token*/ )."&ajax=1".
                                                                                                                  "&configure=".$this->name.
                                                                                                                  "&action="."GenerateOptimImage".
                                                                                                                  "&key=".$this->secure_key.
                                                                                                                  "&id_image=".$id,
                                    'link' => $this->context->link->getImageLink("optim", $imageOpt->id_image, $imageOpt->id_type),
                                    'action' => $this->l('Generate'),
                                    'disable' => !((int)$id > 0),
        ));

        return $this->display(__FILE__, 'views/admin/list_action_generate.tpl');
    }

    public function getSetup()
    {
        foreach ($this->values as $key => $value)
            $this->values[$key] = Configuration::get($key);

        return $this->values;
    }

    public function updateImagesOriginInDb()
    {
        $images = Image::getAllImages();

        $imageTypes = array_filter(ImageTypeCore::getImagesTypes(), function($val, $key) {
            return $val["products"] === "1";
        }, ARRAY_FILTER_USE_BOTH);

        foreach ($images as $key => $image)  {

            if ( count($imageTypes) == count(ImageOpt::findOnesByIdImage($image['id_image'])))
                continue;

            foreach ($imageTypes as $keyType => $valueTypes) {

                $img = new image($image['id_image']);
                $path = _PS_PROD_IMG_DIR_.$img->getExistingImgPath().'-'.$valueTypes['name'].'.'.$img->image_format;
                $info = getimagesize($path);

                $imageOpt = new ImageOpt();

                $imageOpt->id_image = $image["id_image"];
                $imageOpt->id_product = $img->id_image;
                $imageOpt->id_type = stripslashes($valueTypes['name']);
                $imageOpt->width = $info[0];
                $imageOpt->height = $info[1];
                $imageOpt->weight_origin = filesize ( $path );
                $imageOpt->md5_origin = (string)md5($path);
                $imageOpt->path = $path;

                $imageOpt->save();

            }
        }
    }

    public function setSetup()
    {
        foreach ($_POST as $key => $value)
            if (array_key_exists($key, $this->values))
                Configuration::updateValue($key, $value, true);

        return true;
    }

    public function ajaxProcessGenerateOptimImage()
    {
        ob_end_clean();
        header('Content-Type: application/json');

        if ($this->secure_key != Tools::getValue("key"))
            die(json_encode(['error' => 'Error key']));

        if (Tools::isSubmit('id_image'))
            $this->getOptimisedImage(Tools::getValue("id_image"));

        die(json_encode(['error' => 'Error ajaxProcess']));
    }

    public function getOptimisedImage($id_imageOpt = null)
    {
        if ($id_imageOpt == null)
            die(json_encode(['error' => 'Error id']));

        define('WEBSERVICE', 'http://api.resmush.it/ws.php?img=');
        $demo = "https://front.thirtybees.com";
        $sufix = (Configuration::get('IMAGEOPTIM_DEMO') ? '_opt.' : '.');

        $imageOpt = new imageOpt($id_imageOpt);
        $image = new Image($imageOpt->id_image);

        $url = $this->context->link->getImageLink('optim'/*name*/, $imageOpt->id_image/*ids*/, $imageOpt->id_type/*type*/);
        $path = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'-'.$imageOpt->id_type.'.'.$image->image_format;
        $pathOpt = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'-'.$imageOpt->id_type.$sufix.$image->image_format;

        if (Configuration::get('IMAGEOPTIM_LOCAL'))
            $url = str_replace("http://thirtybees", $demo, $url);

        $result = json_decode(file_get_contents(WEBSERVICE . $url));

        if(isset($result->error))
            die(json_encode(['error' => 'Server Error :'+$result->error]));
        else {

            if(!file_put_contents($pathOpt, fopen($result->dest, 'r')))
                die(json_encode(['error' => 'Error during saving new file on disk, may be file not exist']));

            $imageOpt->weight_opt = filesize ( $pathOpt );
            $imageOpt->md5_origin = (string)md5($pathOpt);
            if(!$imageOpt->save())
                die(json_encode(['error' => 'Error during saving imageOpt Object']));

            die(json_encode([
                'dest' => $result->dest,
                'pathOpt' => $pathOpt,
                'imageopt' => $imageOpt
            ]));
        }

        die(json_encode(['error' => 'Error process']));
    }
}
