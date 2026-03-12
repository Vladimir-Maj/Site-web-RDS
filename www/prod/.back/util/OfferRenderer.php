<?php
// .back/util/OfferRenderer.php

class OfferRenderer
{
    public static function render(Offer $offer): string
    {
        // Use the centralized factory we just built!
        return TwigFactory::getTwig()->render('components/card_template.twig', [
            'offer' => $offer,
            // base_url is already a global in TwigFactory, so no need to pass it!
        ]);
    }
}