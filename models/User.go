package models

import (
    "time"
)

type User struct {
    ID        int       `json:"id"`
    Username  string    `json:"username"`
    Email     string    `json:"email"`
    CreatedAt time.Time `json:"created_at"`
    // placeholder_auth will be replaced
    AuthToken string    `json:"auth_token"`
}

func (u *User) Validate() error {
    // placeholder_auth validation logic
    return nil
}
