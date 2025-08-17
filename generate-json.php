<?php

/**
 * GitHub 저장소의 특정 디렉토리 내용을 읽어 JSON 파일로 생성하는 스크립트
 */

// --- 설정 ---
$owner = 'xtx9'; // GitHub 사용자 또는 조직 이름
$repo = 'xtx9-media-storage'; // 저장소 이름
$path = 'imgs'; // 대상 디렉토리 경로
$outputFile = 'media-list.json'; // 생성될 JSON 파일 이름
// --- 설정 끝 ---

// GitHub API URL 생성
$apiUrl = "https://api.github.com/repos/{$owner}/{$repo}/contents/{$path}";

// cURL을 사용하여 API 요청
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// GitHub API는 User-Agent 헤더를 요구합니다.
curl_setopt($ch, CURLOPT_USERAGENT, 'My-WordPress-Plugin-Action');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// API 응답 검증
if ($httpCode !== 200) {
    echo "Error: Failed to fetch data from GitHub API. HTTP Code: {$httpCode}\n";
    echo "Response: {$response}\n";
    exit(1); // 오류 코드를 반환하여 GitHub Actions를 실패 처리
}

$files = json_decode($response, true);

if ($files === null) {
    echo "Error: Failed to decode JSON response from GitHub API.\n";
    exit(1);
}

// 이미지 파일 이름만 추출하여 새로운 배열 생성
$imageList = [];
foreach ($files as $file) {
    // 'type'이 'file'인 경우에만 처리
    if (isset($file['type']) && $file['type'] === 'file') {
        // 이미지 확장자인지 간단히 확인 (필요에 따라 더 정교하게 수정 가능)
        if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file['name'])) {
            $imageList[] = $file['name'];
        }
    }
}

// 최종 JSON 데이터 생성 (가독성을 위해 예쁘게 포맷)
$jsonOutput = json_encode($imageList, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

// 파일로 저장
file_put_contents($outputFile, $jsonOutput);

echo "Successfully generated {$outputFile} with " . count($imageList) . " images.\n";
exit(0); // 성공적으로 종료