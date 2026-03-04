<?php

namespace App\Http\Controllers\Api\v1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CommunityCategory;
use App\Models\CommunityPost;
use App\Models\CommunityLike;
use App\Models\Trainer;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    /**
     * Determine the current audience type (trainer or client)
     */
    private function getAudienceType(): string
    {
        if (auth('trainer')->check()) {
            return 'trainer';
        }
        return 'client';
    }

    /**
     * GET /api/v1/mobile/community/categories
     * Fetch categories visible to the current user type
     */
    public function categories(): JsonResponse
    {
        try {
            $audience = $this->getAudienceType();
            $visibleField = ($audience === 'trainer') ? 'show_to_trainer' : 'show_to_client';

            $categories = CommunityCategory::where('is_active', true)
                ->where($visibleField, true)
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Community categories fetched',
                'data'    => $categories,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v1/mobile/community/posts
     * Fetch posts for the current community
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $audience = $this->getAudienceType();
            
            $query = CommunityPost::with(['author', 'category'])
                ->where('is_active', true)
                ->whereIn('target_audience', [$audience, 'all']);

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('content', 'LIKE', "%{$search}%");
                });
            }

            $posts = $query->latest()
                ->paginate($request->get('per_page', 10));

            return response()->json([
                'status'  => true,
                'message' => 'Community posts fetched',
                'data'    => $posts,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/mobile/community/posts
     * Create a new post
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth('trainer')->user() ?: auth('api')->user();
            $audience = $this->getAudienceType();

            $validated = $request->validate([
                'category_id' => 'required|exists:fb_tbl_community_category,id',
                'title'       => 'required|string|max:255',
                'content'     => 'required|string',
                'image'       => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            ]);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('community_posts', 'public');
                $validated['image'] = $path;
            }

            $post = CommunityPost::create([
                'category_id'     => $validated['category_id'],
                'author_id'       => $user->id,
                'author_type'     => get_class($user),
                'target_audience' => $audience, // Posts created by users target their own community by default
                'title'           => $validated['title'],
                'content'         => $validated['content'],
                'image'           => $validated['image'] ?? null,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Post published successfully',
                'data'    => $post->load(['author', 'category']),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Community post failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/mobile/community/posts/{id}/like
     * Toggle like on a post
     */
    public function toggleLike($id): JsonResponse
    {
        try {
            $user = auth('trainer')->user() ?: auth('api')->user();
            $post = CommunityPost::findOrFail($id);

            $like = CommunityLike::where('post_id', $post->id)
                ->where('user_id', $user->id)
                ->where('user_type', get_class($user))
                ->first();

            if ($like) {
                $like->delete();
                $post->decrement('total_likes');
                $isLiked = false;
            } else {
                CommunityLike::create([
                    'post_id'   => $post->id,
                    'user_id'   => $user->id,
                    'user_type' => get_class($user),
                ]);
                $post->increment('total_likes');
                $isLiked = true;
            }

            return response()->json([
                'status'  => true,
                'message' => $isLiked ? 'Post liked' : 'Post unliked',
                'data'    => [
                    'total_likes' => $post->total_likes,
                    'is_liked'    => $isLiked,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v1/mobile/community/posts/{id}/comments
     * Fetch comments for a specific post
     */
    public function getComments($id): JsonResponse
    {
        try {
            $post = CommunityPost::findOrFail($id);
            $comments = $post->comments()
                ->with('author:id,first_name,last_name,profile_pic')
                ->latest()
                ->paginate(20);

            return response()->json([
                'status'  => true,
                'message' => 'Comments fetched',
                'data'    => $comments,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/mobile/community/posts/{id}/comments
     * Add a comment to a post
     */
    public function comment(Request $request, $id): JsonResponse
    {
        try {
            $user = auth('trainer')->user() ?: auth('api')->user();
            $post = CommunityPost::findOrFail($id);

            $validated = $request->validate([
                'content'   => 'required|string|max:1000',
                'parent_id' => 'nullable|exists:fb_tbl_community_comment,id',
            ]);

            $comment = $post->comments()->create([
                'parent_id'   => $validated['parent_id'] ?? null,
                'author_id'   => $user->id,
                'author_type' => get_class($user),
                'content'     => $validated['content'],
            ]);

            $post->increment('total_comments');

            return response()->json([
                'status'  => true,
                'message' => 'Comment added',
                'data'    => $comment->load('author:id,first_name,last_name,profile_pic'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/mobile/community/posts/{id}/share
     * Increment share count & get share link
     */
    public function share($id): JsonResponse
    {
        try {
            $post = CommunityPost::findOrFail($id);
            $post->increment('total_shares');

            // Generate a simple "deep link" or web link for the share sheet
            $shareUrl = config('app.url') . "/posts/" . $post->id;

            return response()->json([
                'status'  => true,
                'message' => 'Share counted',
                'data'    => [
                    'total_shares' => $post->total_shares,
                    'share_url'    => $shareUrl,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
