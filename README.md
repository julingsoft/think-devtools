# Juling-DevTools

Juling DevTools 是一款开发辅助工具，帮助开发者实现功能的 CRUD 操作，快速实现业务落地。

## 安装

安装 think-devtools 工具 的 composer 包

```
composer require juling/think-devtools --dev
```

## 使用

工具初始化

```
php think gen:init
```

生成数据表实体类

```
php think gen:entity
```

生成数据表模型类

```
php think gen:model
```

生成数据表DAO类

```
php think gen:dao
```

生成数据表服务类

```
php think gen:service
```

## 其他

生成 swagger 接口文档

```
php think gen:swagger
```

生成请求和响应类接口（typescript interface）

```
php think gen:interface
```

## License

Apache-2.0