<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\Hook\LoginButton;

class LoginButton
{
    /**
     * @var string
     */
    protected $logoUrl;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var callable
     */
    protected $onSuccess;

    /**
     * Create an additional button to be displayed below the login form
     *
     * @param callable $onSuccess What to do if the button is pressed
     * @param string $label Text to show on the button
     * @param ?string $logoUrl URL to an image to show on the button before the text
     */
    public function __construct(callable $onSuccess, string $label, ?string $logoUrl = null)
    {
        $this->logoUrl = $logoUrl;
        $this->label = $label;
        $this->onSuccess = $onSuccess;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function setLogoUrl(string $logoUrl): self
    {
        $this->logoUrl = $logoUrl;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getOnSuccess(): callable
    {
        return $this->onSuccess;
    }

    public function setOnSuccess(callable $onSuccess): self
    {
        $this->onSuccess = $onSuccess;

        return $this;
    }
}
