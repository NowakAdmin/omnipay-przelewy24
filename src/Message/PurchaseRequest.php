<?php

namespace Omnipay\Przelewy24\Message;

use Omnipay\Common\Message\ResponseInterface;

/**
 * @todo: Reason this class exists
 */
class PurchaseRequest extends AbstractRequest
{
    private static $apiVersion = '1.0.16';

    public function getSessionId()
    {
        return $this->getParameter('sessionId');
    }

    public function setSessionId($value)
    {
        return $this->setParameter('sessionId', $value);
    }

    public function getEmail()
    {
        return $this->getParameter('email');
    }

    public function setEmail($value)
    {
        return $this->setParameter('email', $value);
    }

    public function getCountry()
    {
        return $this->getParameter('country');
    }

    public function setCountry($value)
    {
        return $this->setParameter('country', $value);
    }

    public function getItems()
    {
        return $this->getParameter('items');
    }

    public function setItems($value)
    {
        return $this->setParameter('items', $value);
    }

    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData()
    {
        $this->validate('sessionId', 'amount', 'currency', 'description', 'card', 'returnUrl', 'notifyUrl');

        $data = array(
            'merchantId' => $this->getmerchantId(),
            'posId' => $this->getPosId(),
            'sessionId' => $this->getSessionId(),
            'amount' => $this->getAmountInteger(),
            'currency' => $this->getCurrency(),
            'description' => $this->getDescription(),
            'email' => $this->getCard()->getEmail(),
            'country' => $this->getCard()->getCountry(),
            //'p24_phone' => $this->getCard()->getPhone(),
            'p24_sign' => $this->generateSignature(
                $this->getSessionId(),
                $this->getPosId(),
                $this->getAmountInteger(),
                $this->getCurrency(),
                $this->getCrc()
            ),
            'urlReturn' => $this->getReturnUrl(),
            'p24_url_status' => $this->getNotifyUrl(),
        );

        if (null !== $this->getChannel()) {
            $data['p24_channel'] = $this->getChannel();
        }

        $items = $this->getItems();
        if ($items) {
            $index = 1;
            foreach ($items as $item) {
                $data['pr24_name_' . $index] = $item['name'];
                $data['pr24_quantity_' . $index] = $item['quantity'];
                $data['pr24_price_' . $index] = $item['price'];
                $data['pr24_description_' . $index] = $item['description'];

                $index++;
            }
        }

        return $data;
    }

    /**
     * Send the request with specified data
     *
     * @param  mixed $data The data to send
     * @return ResponseInterface
     */
    public function sendData($data)
    {
        $httpResponse = $this->sendRequest('POST', 'trnRegister', $data);

        $responseData = array();
        parse_str($httpResponse->getBody(), $responseData);

        return $this->response = new PurchaseResponse($this, $responseData, $this->getEndpoint());
    }

    /**
     * @param $sessionId
     * @param $posId
     * @param $amount
     * @param $currency
     * @param $crc
     * @return string
     */
    private function generateSignature(string $sessionId, int $posId, int $amount, string $currency, string $crc)
    {
        return hash(sprintf('%s|%s|%s|%s|%s', $sessionId, $posId, $amount, $currency, $crc));
    }
}
