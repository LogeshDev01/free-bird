<?php

namespace App\Http\Resources\Api\v1\Trainer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subscriptionData = null;

        if ($this->currentSubscription && $this->currentSubscription->status === 'active') {
            $plan = $this->currentSubscription->plan;
            
            // Format features into key => value pairs exactly like Stripe/SaaS platforms
            $features = [];
            foreach ($plan->features as $planFeature) {
                $featureSlug = $planFeature->feature->slug;
                
                if ($planFeature->feature->type === 'boolean') {
                    $features[$featureSlug] = true;
                } else {
                    // For quota features, calculate remaining
                    $usage = $this->currentSubscription->usages->where('feature_id', $planFeature->feature_id)->first();
                    $used = $usage ? $usage->used_count : 0;
                    $limit = $planFeature->limit;
                    
                    $features[$featureSlug] = [
                        'limit' => $limit === -1 ? 'unlimited' : $limit,
                        'used' => $used,
                        'remaining' => $limit === -1 ? 'unlimited' : max(0, $limit - $used)
                    ];
                }
            }

            $subscriptionData = [
                'id' => $this->currentSubscription->id,
                'plan_name' => $plan->name,
                'plan_slug' => $plan->slug,
                'status' => $this->currentSubscription->status,
                'ends_at' => $this->currentSubscription->ends_at ? \Illuminate\Support\Carbon::parse($this->currentSubscription->ends_at)->format('d M Y') : null,
                'features' => $features
            ];
        }

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'profile_pic' => $this->profile_pic,
            'goal' => $this->goal,
            'city' => $this->city->name ?? null,
            'zone' => $this->zone->name ?? $this->zone ?? null,
            'status' => $this->status,
            'subscription' => $subscriptionData,
            
            // Conditionally loaded relations
            'joined_at' => ($this->pivot && $this->pivot->start_date) ? \Illuminate\Support\Carbon::parse($this->pivot->start_date)->format('d M Y') : null,
            'workout_assignments' => $this->whenLoaded('workoutAssignments'),
            'diet_plan_assignments' => $this->whenLoaded('dietPlanAssignments'),
        ];
    }
}
