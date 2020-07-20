<?php

declare(strict_types=1);

namespace Sendportal\Base\Http\Controllers\Campaigns;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Sendportal\Base\Http\Controllers\Controller;
use Sendportal\Base\Models\Campaign;
use Sendportal\Base\Models\CampaignStatus;
use Sendportal\Base\Repositories\Campaigns\CampaignTenantRepositoryInterface;
use Sendportal\Base\Traits\ResolvesCurrentWorkspace;

class CampaignCancellationController extends Controller
{
    use ResolvesCurrentWorkspace;

    /**
     * @var CampaignTenantRepositoryInterface $campaignRepository
     */
    private $campaignRepository;

    public function __construct(CampaignTenantRepositoryInterface $campaignRepository)
    {
        $this->campaignRepository = $campaignRepository;
    }

    /**
     * @throws Exception
     */
    public function confirm(int $campaignId) {
        $campaign = $this->campaignRepository->find($this->currentWorkspace()->id, $campaignId, ['status']);

        return view('sendportal::campaigns.cancel', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * @throws Exception
     */
    public function cancel(int $campaignId) {
        /** @var Campaign $campaign */
        $campaign = $this->campaignRepository->find($this->currentWorkspace()->id, $campaignId, ['status']);
        $originalStatus = $campaign->status;

        if( ! $campaign->canBeCancelled())
        {
            throw ValidationException::withMessages([
                'campaignStatus' => "{$campaign->status->name} campaigns cannot be cancelled.",
            ])->redirectTo(route('sendportal.campaigns.index'));
        }

        $this->campaignRepository->cancelCampaign($campaign);

        return redirect()->route('sendportal.campaigns.index')->with([
            'success' => $this->getSuccessMessage($originalStatus),
        ]);
    }

    private function getSuccessMessage(CampaignStatus $campaignStatus): string
    {
        if($campaignStatus->id === CampaignStatus::STATUS_QUEUED)
        {
            return "The queued campaign was cancelled successfully.";
        }

        return "The campaign was cancelled whilst being processed.";
    }
}