<?php

/**
 * GitHub 저장소의 특정 디렉토리를 재귀적으로 탐색하여
 * 상세 정보가 포함된 트리 구조의 JSON 파일을 생성하는 스크립트
 */

// --- 설정 ---
$owner = 'xtx9';
$repo = 'xtx9-media-storage';
$rootPath = 'imgs';
$outputFile = 'xtx9-media-storage-media.json';
$cdnBaseUrl = 'https://xtx9.pages.dev'; // <<< 최종 이미지 URL을 위한 베이스 주소
// --- 설정 끝 ---

/**
 * 지정된 경로의 디렉토리 내용을 재귀적으로 가져와 트리 구조로 만듭니다.
 *
 * @param string $path 탐색할 디렉토리 경로
 * @return array|null 해당 디렉토리의 자식 노드 배열 또는 실패 시 null
 */
function fetchDirectoryTree(string $path): ?array
{
    global $owner, $repo, $cdnBaseUrl; // <<< $cdnBaseUrl 전역 변수 사용
    $apiUrl = "https://api.github.com/repos/{$owner}/{$repo}/contents/{$path}";

    // (cURL 로직은 이전과 동일하므로 생략)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'sf-external-media-json-generator');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) { /* ... 오류 처리 ... */
        return null;
    }
    $items = json_decode($response, true);
    if ($items === null) { /* ... 오류 처리 ... */
        return null;
    }

    $children = [];
    foreach ($items as $item) {
        $pathInfo = pathinfo($item['name']);
        $fileNameNoExt = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? '';

        $type = 'file';
        if ($item['type'] === 'dir') {
            $type = 'folder';
        } elseif (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'])) {
            $type = 'img';
        }

        // 각 노드에 대한 정보 구성
        $node = [
            'name'         => $fileNameNoExt,
            'ext'          => $extension,
            'filename'     => $item['name'],
            'type'         => $type,
            'path'         => $item['path'],
            'size'         => $item['size'],
            // <<< [수정] 필드명 변경: url -> githubUrl
            'githubUrl'    => $item['html_url'],
            // <<< [추가] 실제 사용될 최종 CDN URL
            'cdnUrl'       => ($type !== 'folder') ? "{$cdnBaseUrl}/{$item['path']}" : null,
            'Resolution'   => null,
            'lastModified' => null,
        ];

        if ($type === 'folder') {
            $node['children'] = fetchDirectoryTree($item['path']);
        }

        $children[] = $node;
    }

    return $children;
}

// (JSON 생성 및 저장 로직은 이전과 동일)
echo "Generating media list from '{$rootPath}' directory...\n";
$treeChildren = fetchDirectoryTree($rootPath);
$rootNode = [
    'name' => $rootPath,
    'type' => 'folder',
    'path' => $rootPath,
    'children' => $treeChildren
];
$finalJson = ['tree' => $rootNode];
file_put_contents($outputFile, json_encode($finalJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Successfully generated {$outputFile}\n";
exit(0);
