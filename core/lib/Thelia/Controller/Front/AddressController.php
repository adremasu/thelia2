<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace Thelia\Controller\Front;
use Thelia\Core\Event\AddressCreateOrUpdateEvent;
use Thelia\Core\Event\AddressEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Form\AddressCreateForm;
use Thelia\Form\AddressUpdateForm;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Model\AddressQuery;
use Thelia\Model\Customer;
use Thelia\Tools\URL;

/**
 * Class AddressController
 * @package Thelia\Controller\Front
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */
class AddressController extends BaseFrontController
{

    /**
     * Controller for generate modal containing update form
     * Check if request is a XmlHttpRequest and address owner is the current customer
     * @param $address_id
     */
    public function generateModalAction($address_id)
    {

        $this->checkAuth();
        $this->checkXmlHttpRequest();


    }


    /**
     * Create controller.
     * Check if customer is logged in
     *
     * Dispatch TheliaEvents::ADDRESS_CREATE event
     */
    public function createAction()
    {

        $this->checkAuth();

        $addressCreate = new AddressCreateForm($this->getRequest());

        try {
            $customer = $this->getSecurityContext()->getCustomerUser();

            $form = $this->validateForm($addressCreate, "post");
            $event = $this->createAddressEvent($form);
            $event->setCustomer($customer);

            $this->dispatch(TheliaEvents::ADDRESS_CREATE, $event);
            $this->redirectSuccess($addressCreate);

        } catch (FormValidationException $e) {
            $message = sprintf("Please check your input: %s", $e->getMessage());
        } catch (\Exception $e) {
            $message = sprintf("Sorry, an error occured: %s", $e->getMessage());
        }

        if ($message !== false) {
            \Thelia\Log\Tlog::getInstance()->error(sprintf("Error during address creation process : %s", $message));

            $addressCreate->setErrorMessage($message);

            $this->getParserContext()
                ->addForm($addressCreate)
                ->setGeneralError($message)
            ;
        }
    }

    public function updateViewAction($address_id)
    {
        $this->checkAuth();

        $customer = $this->getSecurityContext()->getCustomerUser();
        $address = AddressQuery::create()->findPk($address_id);

        if(!$address || $customer->getId() != $address->getCustomerId()) {
            $this->redirectToRoute("home");
        }

        $this->getParserContext()->set("address_id", $address_id);
    }

    public function processUpdateAction($address_id)
    {
        $this->checkAuth();
        $request = $this->getRequest();

        $addressUpdate = new AddressUpdateForm($request);


        try {
            $customer = $this->getSecurityContext()->getCustomerUser();

            $form = $this->validateForm($addressUpdate);

            $address = AddressQuery::create()->findPk($address_id);

            if (null === $address) {
                $this->redirectToRoute("home");
            }

            if ($address->getCustomer()->getId() != $customer->getId()) {
                $this->redirectToRoute("home");
            }

            $event = $this->createAddressEvent($form);
            $event->setAddress($address);

            $this->dispatch(TheliaEvents::ADDRESS_UPDATE, $event);

            $this->redirectSuccess($addressUpdate);
        } catch (FormValidationException $e) {
            $message = sprintf("Please check your input: %s", $e->getMessage());
        } catch (\Exception $e) {
            $message = sprintf("Sorry, an error occured: %s", $e->getMessage());
        }
        $this->getParserContext()->set("address_id", $address_id);
        if ($message !== false) {
            \Thelia\Log\Tlog::getInstance()->error(sprintf("Error during address creation process : %s", $message));

            $addressUpdate->setErrorMessage($message);

            $this->getParserContext()
                ->addForm($addressUpdate)
                ->setGeneralError($message)
            ;
        }
    }

    public function deleteAction($address_id)
    {
        $this->checkAuth();

        $customer = $this->getSecurityContext()->getCustomerUser();
        $address = AddressQuery::create()->findPk($address_id);

        if(!$address || $customer->getId() != $address->getCustomerId()) {
            $this->redirectToRoute("home");
        }

        $this->dispatch(TheliaEvents::ADDRESS_DELETE, new AddressEvent($address));

        $this->redirectToRoute("customer.account.view");
    }

    protected function createAddressEvent($form)
    {
        return new AddressCreateOrUpdateEvent(
            $form->get("label")->getData(),
            $form->get("title")->getData(),
            $form->get("firstname")->getData(),
            $form->get("lastname")->getData(),
            $form->get("address1")->getData(),
            $form->get("address2")->getData(),
            $form->get("address3")->getData(),
            $form->get("zipcode")->getData(),
            $form->get("city")->getData(),
            $form->get("country")->getData(),
            $form->get("cellphone")->getData(),
            $form->get("phone")->getData(),
            $form->get("company")->getData(),
            $form->get("is_default")->getData()
        );
    }
}
