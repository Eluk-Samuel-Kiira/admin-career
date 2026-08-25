<?php

namespace App\Http\Controllers\Job\JobPosting;

use App\Http\Controllers\Controller;
use App\Services\Jobs\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiJobController extends Controller
{
    protected AiService $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function getModels()
    {
        return response()->json([
            'success' => true,
            'data' => $this->aiService->getAvailableModels(),
            'default' => config('ai.default', 'gemini'),
        ]);
    }

    public function extractJobData(Request $request)
    {
        $request->validate([
            'model' => 'nullable|string',
            'content' => 'required|string',
            'source_type' => 'required|in:text,url',
            'country' => 'nullable|string|size:2',
        ]);

        $model = $request->model ?? config('ai.default');

        try {
            $result = $this->aiService->extractJobData(
                $request->content,
                $request->source_type,
                $model,
                $request->country
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Parse the error message to extract per-model errors
            $modelErrors = $this->parseModelErrors($errorMessage);
            
            Log::error('AI extraction failed', ['model' => $model, 'error' => $errorMessage]);

            return response()->json([
                'success' => false,
                'errors' => $modelErrors,
                'message' => 'AI extraction failed'
            ], 422);
        }
    }

    public function extractFromImage(Request $request)
    {
        $request->validate([
            'model' => 'nullable|string',
            'image_base64' => 'required|string',
            'country' => 'nullable|string|size:2',
        ]);

        $model = $request->model ?? config('ai.default');

        try {
            $result = $this->aiService->extractFromImage($request->image_base64, $model, $request->country);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $modelErrors = $this->parseModelErrors($errorMessage);
            
            Log::error('Image extraction failed', ['model' => $model, 'error' => $errorMessage]);

            return response()->json([
                'success' => false,
                'errors' => $modelErrors,
                'message' => 'Image extraction failed'
            ], 422);
        }
    }

    public function enhanceField(Request $request)
    {
        $request->validate([
            'model' => 'nullable|string',
            'field_name' => 'required|string',
            'content' => 'required|string',
            'instruction' => 'required|string',
        ]);

        $model = $request->model ?? config('ai.default');

        try {
            $enhanced = $this->aiService->enhanceField(
                $request->field_name,
                $request->content,
                $request->instruction,
                $model
            );

            return response()->json([
                'success' => true,
                'enhanced' => $enhanced,
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $modelErrors = $this->parseModelErrors($errorMessage);
            
            Log::error('AI enhance field failed', [
                'model' => $model,
                'field_name' => $request->field_name,
                'error' => $errorMessage,
            ]);
            
            return response()->json([
                'success' => false,
                'errors' => $modelErrors,
                'message' => 'Failed to enhance field'
            ], 422);
        }
    }

    public function generateFromTitle(Request $request)
    {
        $request->validate([
            'model' => 'nullable|string',
            'title' => 'required|string',
            'company' => 'nullable|string',
            'country' => 'nullable|string|size:2',
        ]);

        $model = $request->model ?? config('ai.default');

        try {
            $result = $this->aiService->generateFromTitle(
                $request->title,
                $request->company,
                $request->country,
                $model
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $modelErrors = $this->parseModelErrors($errorMessage);
            
            Log::error('AI generate from title failed', [
                'model' => $model,
                'title' => $request->title,
                'error' => $errorMessage,
            ]);
            
            return response()->json([
                'success' => false,
                'errors' => $modelErrors,
                'message' => 'Failed to generate job posting'
            ], 422);
        }
    }

    /**
     * Parse the error message to extract per-model errors.
     * Now handles both the old string format and the new JSON-encoded format.
     */
    private function parseModelErrors(string $message): array
    {
        // First, try to decode as JSON
        $decoded = json_decode($message, true);
        if (isset($decoded['type']) && $decoded['type'] === 'model_errors' && isset($decoded['errors'])) {
            // Return the errors array directly (model names as keys, error messages as values)
            return $decoded['errors'];
        }

        // Fallback: old parsing logic for string format
        $errors = [];
        
        // Split by " | " to get each model's error
        $parts = explode(' | ', $message);
        
        if (count($parts) > 1) {
            foreach ($parts as $part) {
                if (preg_match('/\[([^\]]+)\]\s*(.*)/', $part, $matches)) {
                    $model = $matches[1];
                    $error = $matches[2];
                    
                    // Try to extract "message" from JSON
                    if (preg_match('/"message"\s*:\s*"([^"]+)"/', $error, $msgMatch)) {
                        $error = $msgMatch[1];
                    } else {
                        // Clean up the error text
                        $error = preg_replace('/HTTP request returned status code \d+:\s*/', '', $error);
                        $error = preg_replace('/\{.*\}/', '', $error);
                        $error = preg_replace('/\\n/', ' ', $error);
                        $error = trim($error);
                    }
                    
                    if (!empty($error)) {
                        $errors[$model] = $error;
                    }
                }
            }
        } else {
            // Single error or unparseable format
            $error = $message;
            
            // Try to extract "message" from JSON
            if (preg_match('/"message"\s*:\s*"([^"]+)"/', $error, $msgMatch)) {
                $error = $msgMatch[1];
            } else {
                $error = preg_replace('/All AI models failed:\s*/', '', $error);
                $error = preg_replace('/HTTP request returned status code \d+:\s*/', '', $error);
                $error = preg_replace('/\{.*\}/', '', $error);
                $error = preg_replace('/\\n/', ' ', $error);
                $error = trim($error);
            }
            
            if (!empty($error)) {
                // Try to determine the model name from the message
                $modelName = 'AI Service';
                if (stripos($message, 'gemini') !== false) $modelName = 'Gemini';
                elseif (stripos($message, 'openai') !== false) $modelName = 'OpenAI';
                elseif (stripos($message, 'claude') !== false) $modelName = 'Claude';
                elseif (stripos($message, 'grok') !== false) $modelName = 'Grok';
                elseif (stripos($message, 'cohere') !== false) $modelName = 'Cohere';
                elseif (stripos($message, 'mistral') !== false) $modelName = 'Mistral';
                
                $errors[$modelName] = $error ?: 'Extraction failed. Please try again.';
            } else {
                $errors['AI Service'] = 'Extraction failed. Please check your API keys and try again.';
            }
        }
        
        return $errors;
    }
}