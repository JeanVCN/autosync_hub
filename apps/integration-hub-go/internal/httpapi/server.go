package httpapi

import (
	"encoding/json"
	"errors"
	"net/http"

	"autosync-hub/integration-hub-go/internal/config"
	autosyncsync "autosync-hub/integration-hub-go/internal/sync"
)

type Server struct {
	config  config.Config
	service *autosyncsync.Service
	mux     *http.ServeMux
}

func NewServer(config config.Config, service *autosyncsync.Service) *Server {
	server := &Server{
		config:  config,
		service: service,
		mux:     http.NewServeMux(),
	}
	server.routes()

	return server
}

func (s *Server) Handler() http.Handler {
	return s.mux
}

func (s *Server) routes() {
	s.mux.HandleFunc("GET /healthz", s.health)
	s.mux.HandleFunc("POST /sync-requests", s.syncRequest)
}

func (s *Server) health(w http.ResponseWriter, r *http.Request) {
	writeJSON(w, http.StatusOK, map[string]string{
		"status":           "ok",
		"contract_version": s.config.ContractVersion,
	})
}

func (s *Server) syncRequest(w http.ResponseWriter, r *http.Request) {
	if !s.authorized(r) {
		writeJSON(w, http.StatusUnauthorized, map[string]string{
			"message": "missing or invalid service token",
		})
		return
	}

	defer r.Body.Close()

	var request autosyncsync.SyncRequest
	if err := json.NewDecoder(r.Body).Decode(&request); err != nil {
		writeJSON(w, http.StatusBadRequest, map[string]string{
			"message": "Malformed JSON payload",
		})
		return
	}

	response, err := s.service.Handle(r.Context(), request)
	if errors.Is(err, autosyncsync.ErrInvalidRequest) {
		writeJSON(w, http.StatusUnprocessableEntity, response)
		return
	}

	if err != nil {
		writeJSON(w, http.StatusInternalServerError, map[string]string{
			"message": "Unexpected sync processing error",
		})
		return
	}

	writeJSON(w, http.StatusAccepted, response)
}

func (s *Server) authorized(r *http.Request) bool {
	if s.config.ServiceToken == "" {
		return true
	}

	return r.Header.Get("Authorization") == "Bearer "+s.config.ServiceToken
}

func writeJSON(w http.ResponseWriter, status int, payload any) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	_ = json.NewEncoder(w).Encode(payload)
}
