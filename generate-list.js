// Node.js에 내장된 파일 시스템(fs) 및 경로(path) 모듈을 가져옵니다.
const fs = require("fs");
const path = require("path");

// --- 설정 ---
// 미디어 파일이 저장된 루트 디렉토리 이름
const mediaRoot = "imgs";
// 생성될 JSON 파일이 저장될 디렉토리 이름
const outputDir = "public";
// 생성될 JSON 파일의 전체 경로
const outputPath = path.join(outputDir, "list.json");
// --- 설정 끝 ---

/**
 * 지정된 디렉토리를 재귀적으로 탐색하여 폴더 구조(tree)와 파일 목록(flatList)을 생성합니다.
 * @param {string} dirPath - 탐색을 시작할 디렉토리의 경로입니다.
 * @param {Array} flatList - 모든 파일 정보를 누적할 배열입니다. (재귀 호출 시 전달됨)
 * @returns {object | null} - 디렉토리 구조를 나타내는 객체(tree)를 반환합니다. 디렉토리가 없으면 null을 반환합니다.
 */
function buildDirectoryTree(dirPath, flatList) {
  // 현재 경로의 내용을 읽어옵니다. 파일/폴더 이름의 배열이 반환됩니다.
  // fs.readdirSync는 동기적으로 작동하여, 작업이 끝날 때까지 다음 코드로 넘어가지 않습니다.
  const entries = fs.readdirSync(dirPath);

  // 현재 디렉토리 정보를 담을 객체를 생성합니다.
  const treeNode = {
    name: path.basename(dirPath), // 경로에서 마지막 부분(이름)을 추출합니다.
    type: "folder",
    // 웹 URL에서 사용하기 좋도록, 윈도우의 '\' 경로 구분자를 '/'로 통일합니다.
    path: dirPath.replace(/\\/g, "/"),
    children: [], // 자식 파일/폴더를 담을 배열
  };

  // 현재 디렉토리의 각 항목(파일 또는 폴더)에 대해 반복 작업을 수행합니다.
  for (const entry of entries) {
    // 전체 경로를 만듭니다. 예: 'imgs' + '2024' -> 'imgs/2024'
    const fullPath = path.join(dirPath, entry);
    // 파일/폴더의 상세 정보(크기, 수정일 등)를 가져옵니다.
    const stats = fs.statSync(fullPath);

    if (stats.isDirectory()) {
      // 만약 현재 항목이 디렉토리(폴더)라면,
      // 자기 자신(buildDirectoryTree)을 다시 호출하여 하위 구조를 가져옵니다. (재귀)
      const subTree = buildDirectoryTree(fullPath, flatList);
      treeNode.children.push(subTree);
    } else {
      // 현재 항목이 파일이라면,
      const filePath = fullPath.replace(/\\/g, "/");
      const fileInfo = {
        name: entry,
        path: filePath,
        size: stats.size, // 파일 크기 (bytes)
        lastModified: stats.mtime.toISOString(), // 최종 수정일 (ISO 8601 형식)
      };

      // 1. 트리 구조에 파일 정보 추가
      treeNode.children.push({
        name: fileInfo.name,
        type: "file",
        path: fileInfo.path,
      });

      // 2. 평평한 목록(flatList)에 파일 정보 추가
      flatList.push(fileInfo);
    }
  }

  return treeNode;
}

// --- 메인 실행 로직 ---
console.log(`'${mediaRoot}' 디렉토리 스캔을 시작합니다...`);

// 미디어 루트 디렉토리가 존재하는지 확인합니다.
if (!fs.existsSync(mediaRoot)) {
  console.error(
    `오류: '${mediaRoot}' 디렉토리를 찾을 수 없습니다. 스크립트를 종료합니다.`
  );
  // process.exit(1)은 오류가 발생했음을 나타내며 프로그램을 종료합니다.
  process.exit(1);
}

// 최종 결과를 담을 두 개의 변수를 초기화합니다.
const flatList = [];
const tree = buildDirectoryTree(mediaRoot, flatList);

// 출력 디렉토리('public')가 없으면 생성합니다.
if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir);
  console.log(`'${outputDir}' 디렉토리를 생성했습니다.`);
}

// 최종 데이터를 JSON 형식의 문자열로 변환합니다.
// JSON.stringify의 세 번째 인자 '2'는 가독성을 위해 2칸 들여쓰기를 적용하라는 의미입니다.
const jsonData = JSON.stringify({ tree, flatList }, null, 2);

// 최종 JSON 문자열을 파일에 씁니다.
fs.writeFileSync(outputPath, jsonData, "utf8");

console.log(
  `성공! 총 ${flatList.length}개의 파일을 찾았고, '${outputPath}' 파일에 저장했습니다.`
);
