<?php
// .back/util/EnumProvider.php

declare(strict_types=1);

class EnumProvider
{
    private OfferRepository $offerRepo;

    public function __construct(OfferRepository $offerRepo)
    {
        $this->offerRepo = $offerRepo;
    }

    /**
     * Returns fixed job types from the Model
     */
    public function getJobTypes(): array
    {
        return OfferModel::JOB_TYPES;
    }

    /**
     * Returns fixed remote types from the Model
     */
    public function getRemoteTypes(): array
    {
        return OfferModel::REMOTE_TYPES;
    }

    /**
     * Returns dynamic locations from the Repository
     */
    public function getLocations(): array
    {
        return $this->offerRepo->getUniqueLocations();
    }
}