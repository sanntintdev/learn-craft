<?php

namespace App\Traits;

trait ApiResponse
{

  protected function error(string $message, int $code = 400, $data = [])
  {
    return response()->json([
      'status' => $code,
      'message' => $message,
      'data' => $data,
    ], $code);
  }

  protected function success(string $message, int $code = 200, $data = [])
  {
    return response()->json([
      'status' => $code,
      'message' => $message,
      'data' => $data,
    ], $code);
  }
}
