<?php

namespace App\Services\Mail;

use Illuminate\Mail\Mailer;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

class CountryMailer
{
    protected ?string $countryCode = null;

    /** @var Address[] */
    protected array $to = [];

    /** @var Address[] */
    protected array $cc = [];

    /** @var Address[] */
    protected array $bcc = [];

    protected ?Address $replyTo = null;

    public function to($addresses, ?string $name = null): self
    {
        $this->to = array_merge($this->to, $this->normalize($addresses, $name));
        return $this;
    }

    public function cc($addresses, ?string $name = null): self
    {
        $this->cc = array_merge($this->cc, $this->normalize($addresses, $name));
        return $this;
    }

    public function bcc($addresses, ?string $name = null): self
    {
        $this->bcc = array_merge($this->bcc, $this->normalize($addresses, $name));
        return $this;
    }

    public function replyTo($address, ?string $name = null): self
    {
        if ($address === null || $address === '') {
            return $this;
        }
        $list = $this->normalize($address, $name);
        $this->replyTo = $list[0] ?? null;
        return $this;
    }

    public function country(?string $countryCode): self
    {
        $this->countryCode = $countryCode ? strtoupper($countryCode) : null;
        return $this;
    }

    public function mailerName(): string
    {
        if ($this->countryCode === null) {
            return Config::get('mail.default', 'smtp');
        }

        $map = Config::get('mailers.map', []);
        return $map[$this->countryCode] ?? Config::get('mail.default', 'smtp');
    }

    public function from(): array
    {
        $defaults = Config::get('mail.from', []);

        if ($this->countryCode === null) {
            return $defaults;
        }

        $overrides = Config::get("mailers.from.{$this->countryCode}", []);

        return [
            'address' => $overrides['address'] ?? $defaults['address'] ?? null,
            'name'    => $overrides['name']    ?? $defaults['name']    ?? config('app.name'),
        ];
    }

    public function mailer(): Mailer
    {
        return Mail::mailer($this->mailerName());
    }

    public function send($mailable): void
    {
        $this->applyHeaders($mailable);
        $pending = $this->mailer()->to($this->to);

        if (!empty($this->cc))  $pending->cc($this->cc);
        if (!empty($this->bcc)) $pending->bcc($this->bcc);

        $pending->send($mailable);
    }

    public function queue($mailable): void
    {
        $this->applyHeaders($mailable);
        $pending = $this->mailer()->to($this->to);

        if (!empty($this->cc))  $pending->cc($this->cc);
        if (!empty($this->bcc)) $pending->bcc($this->bcc);

        $pending->queue($mailable);
    }

    protected function applyHeaders($mailable): void
    {
        if (empty($this->to)) {
            throw new InvalidArgumentException('CountryMailer: no recipient set. Call ->to(...) first.');
        }

        $from = $this->from();
        if (!empty($from['address'])) {
            $mailable->from($from['address'], $from['name'] ?: null);
        }

        // Reply-To belongs on the mailable, never on PendingMail.
        if ($this->replyTo) {
            $mailable->replyTo($this->replyTo->address, $this->replyTo->name ?: null);
        }
    }

    /**
     * Accepts: string | Address | array of strings | array of Address |
     *          ['a@x.com', 'b@y.com'] | ['a@x.com' => 'Name'] |
     *          [['address' => 'a@x.com', 'name' => 'N'], ...] |
     *          stdClass objects with ->address / ->email
     *
     * @return Address[]
     */
    protected function normalize($value, ?string $name = null): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $out = [];

        foreach ($value as $key => $item) {
            // stdClass — cast and recurse
            if ($item instanceof \stdClass) {
                $item = (array) $item;
            }

            if ($item instanceof Address) {
                $out[] = $item;
                continue;
            }

            if (is_string($item)) {
                // ['a@x.com' => 'Display Name'] associative form
                if (is_string($key) && filter_var($key, FILTER_VALIDATE_EMAIL)) {
                    $out[] = new Address($key, $item ?: null);
                } elseif (filter_var($item, FILTER_VALIDATE_EMAIL)) {
                    $out[] = new Address($item, $name ?: null);
                }
                continue;
            }

            if (is_array($item)) {
                $email = $item['address'] ?? $item['email'] ?? null;
                $label = $item['name'] ?? null;
                if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $out[] = new Address($email, $label ?: null);
                }
                continue;
            }
        }

        return $out;
    }
}