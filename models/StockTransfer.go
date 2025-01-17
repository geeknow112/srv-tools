package stock

import (
	"database/sql"
	"time"
)

// StockTransfer short description
//
// long description
type StockTransfer struct {
	Stock
}

// ValidationRules represents validation rules and messages
type ValidationRules struct {
	Rules    map[string]string
	Messages map[string]string
}

// GetValidElement returns validation rules for a specific step
func (st *StockTransfer) GetValidElement(stepNum interface{}) *ValidationRules {
	step1 := &ValidationRules{
		Rules: map[string]string{
			"arrival_dt": "required|max:100",
			// Commented out rules preserved from original
			//"outgoing_warehouse": "required|max:100",
			/*
				"apply_service":    "required|max:100",
				"apply_plan":       "required|max:100",
				... other rules ...
			*/
		},
		Messages: map[string]string{
			"name.required":     "ユーザー名を入力してください",
			"name.string":       "正しい形式で入力してください",
			"name.max":          "文字数をオーバーしています。",
			"email.required":    "メールアドレスを入力してください。",
			"email.email":       "正しい形式でメールアドレスを入力してください",
			"email.max":         "文字数をオーバーしています。",
			"email.unique":      "登録済みのユーザーです",
			"password.required": "パスワードを入力してください",
			"password.min":      "パスワードは8文字以上で入力してください。",
			"password.confirmed": "パスワードが一致しません。",
		},
	}

	return step1
}

// RegDetail registers stock transfer information
func (st *StockTransfer) RegDetail(get interface{}, post *Post) (*StockResult, error) {
	post.TransferFg = true // Transfer process flag
	rows, err := st.Stock.RegDetail(get, post)
	if err != nil {
		return nil, err
	}

	// Process stock reduction for Tamba SP
	stocks := make(map[int][]*StockDetail)
	for i, goods := range rows.GoodsList {
		qty := rows.QtyList[i]
		rwh := rows.ReceiveWarehouse[i]

		if goods == "" || qty == 0 || rwh == "" || rwh == "2" {
			continue
		}

		stockDetails, err := st.GetDetailByGoodsCode(goods, qty)
		if err != nil {
			return nil, err
		}
		stocks[i] = stockDetails
	}

	for _, stock := range stocks {
		for _, d := range stock {
			if err := st.UpdTransferFg(d.ID); err != nil {
				return nil, err
			}
		}
	}

	return rows, nil
}

// GetDetailByGoodsCode retrieves stock details by goods code
func (st *StockTransfer) GetDetailByGoodsCode(goods string, qty int) ([]*StockDetail, error) {
	query := `
		SELECT st.*, std.id, std.lot, std.barcode, std.transfer_fg 
		FROM %s as st 
		LEFT JOIN yc_stock_detail as std ON st.stock = std.stock 
		WHERE st.goods = ? 
		AND st.warehouse = '2'
		AND std.transfer_fg != '1'
		LIMIT 0, ?`

	query = fmt.Sprintf(query, st.GetTableName())
	rows, err := st.db.Query(query, goods, qty)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var details []*StockDetail
	for rows.Next() {
		detail := &StockDetail{}
		if err := rows.Scan(&detail); err != nil {
			return nil, err
		}
		details = append(details, detail)
	}

	return details, nil
}

// UpdTransferFg updates the transfer flag in stock details
func (st *StockTransfer) UpdTransferFg(id int) error {
	query := `
		UPDATE yc_stock_detail 
		SET transfer_fg = ?, updt = ? 
		WHERE id = ?`

	_, err := st.db.Exec(query, true, time.Now().Format("2006-01-02 15:04:05"), id)
	return err
}

// CancelTransfer cancels stock transfer
func (st *StockTransfer) CancelTransfer(stock string) error {
	query := `
		DELETE st FROM %s as st 
		LEFT JOIN yc_stock_detail as std ON st.stock = std.stock 
		WHERE st.stock = ? 
		AND st.transfer_fg = '1'`

	query = fmt.Sprintf(query, st.GetTableName())
	_, err := st.db.Exec(query, stock)
	return err
}
