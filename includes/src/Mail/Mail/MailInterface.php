<?php declare(strict_types=1);

namespace JTL\Mail\Mail;

use JTL\Language\LanguageModel;
use JTL\Mail\Template\TemplateFactory;
use JTL\Mail\Template\TemplateInterface;

/**
 * Interface MailInterface
 * @package JTL\Mail\Mail
 */
interface MailInterface
{
    /**
     * @param string               $id
     * @param mixed                $data
     * @param TemplateFactory|null $factory
     * @return MailInterface
     */
    public function createFromTemplateID(string $id, $data = null, TemplateFactory $factory = null): MailInterface;

    /**
     * @param TemplateInterface  $template
     * @param mixed              $data
     * @param LanguageModel|null $language
     * @return MailInterface
     */
    public function createFromTemplate(TemplateInterface $template, $data = null, $language = null): MailInterface;

    /**
     * @return LanguageModel
     */
    public function getLanguage(): LanguageModel;

    /**
     * @param LanguageModel $language
     * @return MailInterface
     */
    public function setLanguage(LanguageModel $language): self;

    /**
     * @return mixed
     */
    public function getData();

    /**
     * @param mixed $data
     * @return MailInterface
     */
    public function setData($data): self;

    /**
     * @return int
     */
    public function getCustomerGroupID(): int;

    /**
     * @param int $customerGroupID
     * @return MailInterface
     */
    public function setCustomerGroupID(int $customerGroupID): self;

    /**
     * @return string
     */
    public function getFromMail(): string;

    /**
     * @param mixed $fromMail
     * @return MailInterface
     */
    public function setFromMail($fromMail): self;

    /**
     * @return string
     */
    public function getFromName(): string;

    /**
     * @param string $fromName
     * @return MailInterface
     */
    public function setFromName($fromName): self;

    /**
     * @return string
     */
    public function getToMail(): string;

    /**
     * @param mixed $toMail
     * @return MailInterface
     */
    public function setToMail($toMail): self;

    /**
     * @return string
     */
    public function getToName(): string;

    /**
     * @param string $toName
     * @return MailInterface
     */
    public function setToName($toName): self;

    /**
     * @return string
     */
    public function getReplyToMail(): string;

    /**
     * @param string $replyToMail
     * @return MailInterface
     */
    public function setReplyToMail($replyToMail): self;

    /**
     * @return string
     */
    public function getReplyToName(): string;

    /**
     * @param string $replyToName
     * @return MailInterface
     */
    public function setReplyToName(string $replyToName): self;

    /**
     * @return string
     */
    public function getSubject(): string;

    /**
     * @param mixed $subject
     * @return MailInterface
     */
    public function setSubject($subject): self;

    /**
     * @return string
     */
    public function getBodyHTML(): string;

    /**
     * @param string $bodyHTML
     * @return MailInterface
     */
    public function setBodyHTML(string $bodyHTML): self;

    /**
     * @return string
     */
    public function getBodyText(): string;

    /**
     * @param string $bodyText
     * @return MailInterface
     */
    public function setBodyText($bodyText): self;

    /**
     * @return Attachment[]
     */
    public function getAttachments(): array;

    /**
     * @param array $attachments
     * @return MailInterface
     */
    public function setAttachments(array $attachments): self;

    /**
     * @param Attachment $attachment
     */
    public function addAttachment(Attachment $attachment): void;

    /**
     * @return Attachment[]
     */
    public function getPdfAttachments(): array;

    /**
     * @param Attachment[] $pdfAttachments
     * @return MailInterface
     */
    public function setPdfAttachments(array $pdfAttachments): self;

    /**
     * @param Attachment $pdf
     */
    public function addPdfAttachment(Attachment $pdf): void;

    /**
     * @param string $name
     * @param string $file
     */
    public function addPdfFile(string $name, string $file): void;

    /**
     * @return string
     */
    public function getError(): string;

    /**
     * @param string $error
     * @return MailInterface
     */
    public function setError(string $error): self;

    /**
     * @return array
     */
    public function getCopyRecipients(): array;

    /**
     * @param array $copyRecipients
     * @return MailInterface
     */
    public function setCopyRecipients(array $copyRecipients): self;

    /**
     * @param string $copyRecipient
     */
    public function addCopyRecipient(string $copyRecipient): void;

    /**
     * @return TemplateInterface|null
     */
    public function getTemplate(): ?TemplateInterface;

    /**
     * @param TemplateInterface|null $template
     * @return MailInterface
     */
    public function setTemplate(?TemplateInterface $template): self;
}
