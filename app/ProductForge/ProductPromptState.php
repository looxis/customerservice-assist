<?php

namespace App\ProductForge;

/**
 * The product section while an analysis runs: built once the knowledge has been chosen, and made
 * smaller again if rules loaded later need the room (binding knowledge always comes first).
 */
final class ProductPromptState
{
    private ProductPrompt $prompt;

    public function __construct(
        private readonly ProductPromptBuilder $builder,
        public readonly ProductPackage $package,
        public readonly ?string $channel,
    ) {
        $this->prompt = ProductPrompt::none();
    }

    public function fit(int $room): void
    {
        $this->prompt = $this->builder->build($this->package, $this->channel, $room);
    }

    public function shrink(int $characters): void
    {
        $this->fit(max(0, $this->prompt->characters() - $characters));
    }

    public function prompt(): ProductPrompt
    {
        return $this->prompt;
    }

    public function text(): string
    {
        return $this->prompt->text;
    }

    public function characters(): int
    {
        return $this->prompt->characters();
    }
}
