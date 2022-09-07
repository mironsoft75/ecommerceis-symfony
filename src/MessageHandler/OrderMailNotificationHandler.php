<?php

namespace App\MessageHandler;

use App\Entity\Customer;
use App\Entity\OrderProduct;
use App\Message\OrderMailNotification;
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Email;

class OrderMailNotificationHandler implements MessageHandlerInterface
{
    private LoggerInterface $logger;
    private MailerInterface $mailer;

    public function __construct(LoggerInterface $logger, MailerInterface $mailer)
    {
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    public function __invoke(OrderMailNotification $orderMailNotification)
    {
        $contect = $this->prepareHtml($orderMailNotification->getOrderProducts());
        $this->sendMail($orderMailNotification->getCustomer(), $contect);
    }

    /**
     * @param Collection<int, OrderProduct> $orderProducts
     * @return string
     */
    public function prepareHtml(Collection $orderProducts): string
    {
        $html = '<table>';
        $html .= '<tr>';
        $html .= '<tr>';
            $html .= '<th>Ürün Adı</th>';
            $html .= '<th>Adet</th>';
            $html .= '<th>Birim Fiyatı</th>';
            $html .= '<th>Toplam</th>';
        $html .= '</tr>';
        $html .= '<tbody>';
        foreach ($orderProducts as $orderProduct){
            $html .= '<tr>';
                $html .= '<td>'.$orderProduct->getProduct()->getName().'</td>';
                $html .= '<td>'.$orderProduct->getQuantity().'</td>';
                $html .= '<td>'.$orderProduct->getUnitPrice().'</td>';
                $html .= '<td>'.$orderProduct->getTotal().'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }

    public function sendMail(Customer $customer, string $content)
    {
        $email = (new Email())
            ->from('muratcakmakis@yandex.com')
            ->to($customer->getMail())
            ->subject( $customer->getName().' Siparişiniz Bilgileriniz')
            ->text('Sipariş bilgileriniz')
            ->html($content);
        $this->mailer->send($email);
        $this->logger->info('Mail sent');
    }
}