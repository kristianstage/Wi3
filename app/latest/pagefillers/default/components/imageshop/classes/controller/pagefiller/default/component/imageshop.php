<?php defined('SYSPATH') or die ('No direct script access.'); ?>

<?php

    class Controller_Pagefiller_Default_Component_imageshop extends Controller_Pagefiller_Default_Component_Base
    {
    
        public $template;
        protected $componentname = "imageshop";
    
        public function before() 
        {
            Wi3::inst()->acl->grant("*", $this, "login"); // Everybody can access login and logout function in this controller
            Wi3::inst()->acl->grant("*", $this, "logout");
            Wi3::inst()->acl->grant("*", $this, "order");
            Wi3::inst()->acl->grant("admin", $this); // Admin role can access every function in this controller
            Wi3::inst()->acl->check($this);
        }
        
        public function login()
        {
            
        }
    
        public function action_startEditImages() 
        {
            $fieldid = $_POST["fieldid"];
            $field = Wi3::inst()->model->factory("site_field")->set("id", $fieldid)->load();
            $html = $this->view("edit".$this->componentname)->set("field", $field)->render();
            echo json_encode(
                Array(
                    "scriptsbefore" => Array(
                        "0" => "wi3.popup.content='" . addcslashes($html, "'") . "'"
                    ),
                    /*"dom" => Array(
                        "fill" => Array(
                            "div[type=popuphtml]" => $html
                        )
                    ),*/
                    "scriptsafter" => Array(
                        "0" => "wi3.popup.show();"
                    )
                )
            );
        }
        
        public function action_editImage() 
        {
            // Load field, and the image-date field that connects to it
            $fieldid = $_POST["fieldid"];
            $field = Wi3::inst()->model->factory("site_field")->set("id", $fieldid)->load();
            
            // Update e-mailaddress
            $email = Wi3::inst()->model->factory("site_data")->setref($field)->setname("emailaddress")->load();
            // If data does not exist, create it
            if (!$email->loaded())
            {
                $email->create();
            }
            // Update data field with image-id
            $emailaddress = $_POST["emailaddress"];
            $email->data = $emailaddress;
            $email->update();
            
            echo Kohana::debug($email); exit;
            
            // Update folder
            $data = Wi3::inst()->model->factory("site_data")->setref($field)->setname("folder")->load();
            // If data does not exist, create it
            if (!$data->loaded())
            {
                $data->create();
            }
            // Update data field with image-id
            $fileid = $_POST["image"];
            $data->data = $fileid;
            $data->update();
            
            //$file = Wi3::inst()->model->factory("site_file")->set("id", $fileid)->load();
            
            echo json_encode(
                Array(
                    "scriptsbefore" => Array(
                        "0" => "$('[type=field][fieldid=" . $fieldid . "] [type=fieldcontent]').html('" . $field->render() . "');"
                    )
                )
            );
        }
        
        public function action_order() {
            // Load field and check if user is allowed to access data of that field
            $field = $this->field($_POST["fieldid"]);
            // Load pre-set emailaddress
            $address = Wi3::inst()->model->factory("site_data")->setref($field)->setname("emailaddress")->load()->data;
            if (empty($address)) {
                echo json_encode(
                    Array(
                        "scriptsbefore" => Array(
                            "0" => "$(wi3.popup.getDOM()).fadeOut(100,function() { $(this).html('Bestelling kon <strong>niet</strong> geplaatst worden! Stel de eigenaar van de site hiervan op de hoogte.').fadeIn(); } );"
                        )
                    )
                );
            } else {
                // Send an email to client and to the pre-set emailaddress
                $presetemailaddress = $address;
                // Set folder to $_POST to store
                $folderid = Wi3::inst()->model->factory("site_data")->setref($field)->setname("folder")->load()->data;
                $_POST["folderid"] = $folderid;
                // Store order
                $orders = Wi3::inst()->model->factory("site_data")->setref($field)->setname("orders")->load();
                if (!$orders->loaded())
                {
                    $orders->create();
                }
                $ordersdata = unserialize($orders->data);
                $ordersdata[] = $_POST;
                $orders->data = serialize($ordersdata);
                $orders->update();
                // Create mail
                $message = $this->view("mail")->set("post", $_POST)->render();
                $clientmessage = $this->view("clientmail")->set("post", $_POST)->render();
                $subject = "Bestelling foto's";
                // Send mail to 'us' and to client
                mail($presetemailaddress, $subject, $message);
                mail($_POST["emailaddress"], $subject, $clientmessage);
                echo json_encode(
                    Array(
                        "scriptsbefore" => Array(
                            "0" => "$(wi3.popup.getDOM()).fadeOut(100,function() { $(this).html('Bestelling succesvol geplaatst!').fadeIn(); } );"
                        )
                    )
                );
            }
        }
        
    }

?>
