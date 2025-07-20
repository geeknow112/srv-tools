#!/bin/bash

# Universal GitHub Shell Manager Installer
# このスクリプトは githubsh-universal.php を任意のプロジェクトにインストールします

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
GITHUBSH_FILE="$SCRIPT_DIR/githubsh-universal.php"

# 色付きメッセージ用の関数
print_info() {
    echo -e "\033[34m[INFO]\033[0m $1"
}

print_success() {
    echo -e "\033[32m[SUCCESS]\033[0m $1"
}

print_error() {
    echo -e "\033[31m[ERROR]\033[0m $1"
}

print_warning() {
    echo -e "\033[33m[WARNING]\033[0m $1"
}

# 使用方法を表示
show_usage() {
    echo "Universal GitHub Shell Manager Installer"
    echo ""
    echo "Usage:"
    echo "  $0 [target_directory]"
    echo ""
    echo "Examples:"
    echo "  $0                    # Install to current directory"
    echo "  $0 /path/to/project   # Install to specific directory"
    echo "  $0 --global           # Install globally (requires sudo)"
}

# グローバルインストール
install_globally() {
    print_info "Installing globally..."
    
    if [ ! -f "$GITHUBSH_FILE" ]; then
        print_error "githubsh-universal.php not found in $SCRIPT_DIR"
        exit 1
    fi
    
    # /usr/local/bin にコピー
    sudo cp "$GITHUBSH_FILE" /usr/local/bin/githubsh
    sudo chmod +x /usr/local/bin/githubsh
    
    print_success "Installed globally as 'githubsh' command"
    print_info "You can now use 'githubsh' from anywhere"
    
    # 使用例を表示
    echo ""
    echo "Usage examples:"
    echo "  githubsh init"
    echo "  githubsh \"Fix bug\" 0"
    echo "  githubsh project#123 1"
}

# ローカルインストール
install_locally() {
    local target_dir="$1"
    
    if [ -z "$target_dir" ]; then
        target_dir="$(pwd)"
    fi
    
    # ディレクトリの存在確認
    if [ ! -d "$target_dir" ]; then
        print_error "Target directory does not exist: $target_dir"
        exit 1
    fi
    
    print_info "Installing to: $target_dir"
    
    if [ ! -f "$GITHUBSH_FILE" ]; then
        print_error "githubsh-universal.php not found in $SCRIPT_DIR"
        exit 1
    fi
    
    # ファイルをコピー
    cp "$GITHUBSH_FILE" "$target_dir/githubsh.php"
    chmod +x "$target_dir/githubsh.php"
    
    print_success "Installed to $target_dir/githubsh.php"
    
    # 初期化を提案
    echo ""
    print_info "To get started, run:"
    echo "  cd $target_dir"
    echo "  php githubsh.php init"
}

# 依存関係チェック
check_dependencies() {
    print_info "Checking dependencies..."
    
    # PHP チェック
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed or not in PATH"
        exit 1
    fi
    
    local php_version=$(php -r "echo PHP_VERSION;")
    print_info "PHP version: $php_version"
    
    # Git チェック
    if ! command -v git &> /dev/null; then
        print_warning "Git is not installed. Some features may not work."
    else
        local git_version=$(git --version)
        print_info "Git: $git_version"
    fi
    
    # GitHub CLI チェック
    if ! command -v gh &> /dev/null; then
        print_warning "GitHub CLI (gh) is not installed. Issue creation will not work."
        print_info "Install GitHub CLI: https://cli.github.com/"
    else
        local gh_version=$(gh --version | head -n1)
        print_info "GitHub CLI: $gh_version"
    fi
    
    print_success "Dependency check completed"
}

# メイン処理
main() {
    echo "Universal GitHub Shell Manager Installer"
    echo "========================================"
    
    # 引数チェック
    if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
        show_usage
        exit 0
    fi
    
    # 依存関係チェック
    check_dependencies
    echo ""
    
    # インストール実行
    if [ "$1" = "--global" ]; then
        install_globally
    else
        install_locally "$1"
    fi
    
    echo ""
    print_success "Installation completed!"
}

# スクリプト実行
main "$@"
