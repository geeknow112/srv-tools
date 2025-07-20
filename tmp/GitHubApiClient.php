<?php
/**
 * GitHub API Client
 * GitHub CLI の代替として GitHub REST API を使用
 * 
 * Version: 1.0
 * Author: Amazon Q
 * License: MIT
 */

class GitHubApiClient {
    private $token;
    private $owner;
    private $repo;
    private $base_url = 'https://api.github.com';
    
    public function __construct($owner, $repo, $token = null) {
        $this->owner = $owner;
        $this->repo = $repo;
        $this->token = $token ?: $this->getTokenFromEnvironment();
        
        if (!$this->token) {
            throw new Exception("GitHub token is required. Set GITHUB_TOKEN environment variable or pass token directly.");
        }
    }
    
    /**
     * 環境変数からトークンを取得
     */
    private function getTokenFromEnvironment() {
        // 複数の環境変数をチェック
        $token_vars = ['GITHUB_TOKEN', 'GH_TOKEN', 'GITHUB_API_TOKEN'];
        
        foreach ($token_vars as $var) {
            $token = getenv($var);
            if ($token) {
                return $token;
            }
        }
        
        // ~/.github/token ファイルからも読み込み試行
        $token_file = $_SERVER['HOME'] . '/.github/token';
        if (file_exists($token_file)) {
            return trim(file_get_contents($token_file));
        }
        
        return null;
    }
    
    /**
     * HTTP リクエストを実行
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->base_url . $endpoint;
        
        $headers = [
            'Authorization: token ' . $this->token,
            'User-Agent: Universal-GitHub-Shell-Manager/2.0',
            'Accept: application/vnd.github.v3+json',
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: $error");
        }
        
        $decoded_response = json_decode($response, true);
        
        if ($http_code >= 400) {
            $error_message = isset($decoded_response['message']) 
                ? $decoded_response['message'] 
                : "HTTP $http_code error";
            throw new Exception("GitHub API error: $error_message (HTTP $http_code)");
        }
        
        return $decoded_response;
    }
    
    /**
     * Issue を作成
     */
    public function createIssue($title, $body, $labels = [], $assignees = []) {
        $data = [
            'title' => $title,
            'body' => $body
        ];
        
        if (!empty($labels)) {
            $data['labels'] = $labels;
        }
        
        if (!empty($assignees)) {
            $data['assignees'] = $assignees;
        }
        
        $endpoint = "/repos/{$this->owner}/{$this->repo}/issues";
        $response = $this->makeRequest('POST', $endpoint, $data);
        
        return [
            'number' => $response['number'],
            'url' => $response['html_url'],
            'api_url' => $response['url']
        ];
    }
    
    /**
     * Issue にコメントを追加
     */
    public function addIssueComment($issue_number, $body) {
        $data = ['body' => $body];
        $endpoint = "/repos/{$this->owner}/{$this->repo}/issues/{$issue_number}/comments";
        $response = $this->makeRequest('POST', $endpoint, $data);
        
        return [
            'id' => $response['id'],
            'url' => $response['html_url']
        ];
    }
    
    /**
     * Issue をクローズ
     */
    public function closeIssue($issue_number, $comment = null) {
        // コメントがある場合は先に追加
        if ($comment) {
            $this->addIssueComment($issue_number, $comment);
        }
        
        // Issue をクローズ
        $data = ['state' => 'closed'];
        $endpoint = "/repos/{$this->owner}/{$this->repo}/issues/{$issue_number}";
        $response = $this->makeRequest('PATCH', $endpoint, $data);
        
        return [
            'number' => $response['number'],
            'state' => $response['state'],
            'url' => $response['html_url']
        ];
    }
    
    /**
     * Pull Request を作成
     */
    public function createPullRequest($title, $body, $head_branch, $base_branch = 'main') {
        $data = [
            'title' => $title,
            'body' => $body,
            'head' => $head_branch,
            'base' => $base_branch
        ];
        
        $endpoint = "/repos/{$this->owner}/{$this->repo}/pulls";
        $response = $this->makeRequest('POST', $endpoint, $data);
        
        return [
            'number' => $response['number'],
            'url' => $response['html_url'],
            'api_url' => $response['url']
        ];
    }
    
    /**
     * Pull Request の状態を取得
     */
    public function getPullRequest($pr_number) {
        $endpoint = "/repos/{$this->owner}/{$this->repo}/pulls/{$pr_number}";
        $response = $this->makeRequest('GET', $endpoint);
        
        return [
            'number' => $response['number'],
            'state' => $response['state'],
            'merged' => $response['merged'],
            'url' => $response['html_url']
        ];
    }
    
    /**
     * Issue の状態を取得
     */
    public function getIssue($issue_number) {
        $endpoint = "/repos/{$this->owner}/{$this->repo}/issues/{$issue_number}";
        $response = $this->makeRequest('GET', $endpoint);
        
        return [
            'number' => $response['number'],
            'state' => $response['state'],
            'title' => $response['title'],
            'url' => $response['html_url']
        ];
    }
    
    /**
     * リポジトリ情報を取得
     */
    public function getRepository() {
        $endpoint = "/repos/{$this->owner}/{$this->repo}";
        $response = $this->makeRequest('GET', $endpoint);
        
        return [
            'name' => $response['name'],
            'full_name' => $response['full_name'],
            'url' => $response['html_url'],
            'default_branch' => $response['default_branch']
        ];
    }
    
    /**
     * 認証テスト
     */
    public function testAuthentication() {
        try {
            $endpoint = "/user";
            $response = $this->makeRequest('GET', $endpoint);
            return [
                'success' => true,
                'user' => $response['login'],
                'name' => $response['name'] ?? $response['login']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * レート制限情報を取得
     */
    public function getRateLimit() {
        $endpoint = "/rate_limit";
        $response = $this->makeRequest('GET', $endpoint);
        
        return [
            'limit' => $response['rate']['limit'],
            'remaining' => $response['rate']['remaining'],
            'reset' => date('Y-m-d H:i:s', $response['rate']['reset'])
        ];
    }
}
