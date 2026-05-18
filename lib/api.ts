import axios, { AxiosError, AxiosInstance } from "axios";

const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/+$/, "") ||
  "http://localhost:8000/api";
const TOKEN_STORAGE_KEY = "bedevbis_token";

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
  errors?: Record<string, string[]> | string[] | null;
}

export interface User {
  id: number;
  name: string;
  email: string;
  role: "seller" | "buyer";
  created_at: string;
}

export interface AuthData {
  user: User;
  token: string;
}

export interface LoginPayload {
  email: string;
  password: string;
}

export interface RegisterPayload {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  role: "seller" | "buyer";
}

export interface Product {
  id: number;
  seller_id: number;
  name: string;
  description: string;
  price: number;
  stock: number;
  image_url: string | null;
  status: "active" | "inactive";
  created_at: string;
  updated_at: string;
}

export interface ProductListResponse {
  data: Product[];
  pagination?: {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
}

const isBrowser = () => typeof window !== "undefined";

export const getToken = (): string | null => {
  if (!isBrowser()) return null;
  return localStorage.getItem(TOKEN_STORAGE_KEY);
};

export const setToken = (token: string) => {
  if (!isBrowser()) return;
  localStorage.setItem(TOKEN_STORAGE_KEY, token);
};

export const clearToken = () => {
  if (!isBrowser()) return;
  localStorage.removeItem(TOKEN_STORAGE_KEY);
};

export const api: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
});

api.interceptors.request.use((config) => {
  const token = getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      clearToken();
    }
    return Promise.reject(error);
  }
);

export const authApi = {
  async login(payload: LoginPayload): Promise<ApiResponse<AuthData>> {
    const response = await api.post<ApiResponse<AuthData>>("/auth/login", payload);
    if (response.data?.data?.token) {
      setToken(response.data.data.token);
    }
    return response.data;
  },

  async register(payload: RegisterPayload): Promise<ApiResponse<AuthData>> {
    const response = await api.post<ApiResponse<AuthData>>("/auth/register", payload);
    if (response.data?.data?.token) {
      setToken(response.data.data.token);
    }
    return response.data;
  },

  async logout(): Promise<ApiResponse<null>> {
    try {
      const response = await api.post<ApiResponse<null>>("/auth/logout");
      return response.data;
    } finally {
      clearToken();
    }
  },

  async me(): Promise<ApiResponse<User>> {
    const response = await api.get<ApiResponse<User>>("/me");
    return response.data;
  },
};

export const productApi = {
  async list(params?: {
    search?: string;
    min_price?: number;
    max_price?: number;
    page?: number;
  }): Promise<ApiResponse<Product[]> & { pagination?: ProductListResponse["pagination"] }> {
    const response = await api.get<
      ApiResponse<Product[]> & { pagination?: ProductListResponse["pagination"] }
    >("/products", {
      params,
    });
    return response.data;
  },

  async detail(id: number | string): Promise<ApiResponse<Product>> {
    const response = await api.get<ApiResponse<Product>>(`/products/${id}`);
    return response.data;
  },
};

export const getApiErrorMessage = (
  error: unknown,
  fallback = "Terjadi kesalahan, silakan coba lagi."
): string => {
  if (axios.isAxiosError(error)) {
    const responseData = error.response?.data as
      | { message?: string; errors?: Record<string, string[]> | string[] }
      | undefined;

    if (responseData?.message) {
      return responseData.message;
    }

    if (responseData?.errors) {
      if (Array.isArray(responseData.errors) && responseData.errors.length > 0) {
        return responseData.errors[0];
      }

      const firstKey = Object.keys(responseData.errors)[0];
      if (firstKey && responseData.errors[firstKey]?.[0]) {
        return responseData.errors[firstKey][0];
      }
    }
  }

  return fallback;
};

export const isAuthenticated = () => Boolean(getToken());

