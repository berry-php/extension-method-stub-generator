# Berry Extension Method Stub Generator

A composer plugin to generate berry extension method stubs

## How it works

This composer plugin will scan all your dependencies for a file called `berry-method-extensions.json` and generate stubs
in your project to be used by your IDE and phpstan

## How to create stubs

Here is an example we use at [berry/htmx](https://github.com/berry-php/htmx):

```json
{
    "extensions": [
        {
            "namespace": "Berry\\Html",
            "class": "HtmlTag",
            "uses": [
                "Berry\\Htmx\\HxSwap",
                "Berry\\Htmx\\HxTarget"
            ],
            "methods": [
                {
                    "name": "hxGet",
                    "doc": "Issues a GET request to the specified URL",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "url"
                        }
                    ]
                },
                {
                    "name": "hxPost",
                    "doc": "Issues a POST request to the specified URL",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "url"
                        }
                    ]
                },
                {
                    "name": "hxPut",
                    "doc": "Issues a PUT request to the specified URL",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "url"
                        }
                    ]
                },
                {
                    "name": "hxPatch",
                    "doc": "Issues a PATCH request to the specified URL",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "url"
                        }
                    ]
                },
                {
                    "name": "hxDelete",
                    "doc": "Issues a DELETE request to the specified URL",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "url"
                        }
                    ]
                },
                {
                    "name": "hxBoost",
                    "doc": "Enables progressive enhancement",
                    "returns": "static",
                    "args": [
                        {
                            "type": "bool",
                            "name": "value"
                        }
                    ]
                },
                {
                    "name": "hxConfirm",
                    "doc": "Shows a confirm() dialog before issuing requests",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "message"
                        }
                    ]
                },
                {
                    "name": "hxDisable",
                    "doc": "Disables HTMX processing on this element and its children",
                    "returns": "static",
                    "args": []
                },
                {
                    "name": "hxEncoding",
                    "doc": "Changes encoding to multipart/form-data (\"multipart/form-data\")",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "type"
                        }
                    ]
                },
                {
                    "name": "hxExt",
                    "doc": "Extensions to include (comma-separated)",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "extensions"
                        }
                    ]
                },
                {
                    "name": "hxHistory",
                    "doc": "Prevents history snapshot for this element",
                    "returns": "static",
                    "args": [
                        {
                            "type": "bool",
                            "name": "history"
                        }
                    ]
                },
                {
                    "name": "hxInclude",
                    "doc": "Additional elements to include in requests",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "selector"
                        }
                    ]
                },
                {
                    "name": "hxIndicator",
                    "doc": "CSS selector for indicator element",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "selector"
                        }
                    ]
                },
                {
                    "name": "hxParams",
                    "doc": "Filters parameters sent with requests",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "filter"
                        }
                    ]
                },
                {
                    "name": "hxPreserve",
                    "doc": "Preserves element across swaps",
                    "returns": "static",
                    "args": []
                },
                {
                    "name": "hxPrompt",
                    "doc": "Shows a prompt() before issuing requests",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "message"
                        }
                    ]
                },
                {
                    "name": "hxPushUrl",
                    "doc": "Pushes URL into history (\"true\" for current, \"false\" to disable)",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string|bool",
                            "name": "url"
                        }
                    ]
                },
                {
                    "name": "hxReplaceUrl",
                    "doc": "Replaces current URL without push",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string|bool",
                            "name": "url"
                        }
                    ]
                },
                {
                    "name": "hxRequest",
                    "doc": "Configures requests (JSON string)",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "config"
                        }
                    ]
                },
                {
                    "name": "hxSelect",
                    "doc": "Selects content from response to swap",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "selector"
                        }
                    ]
                },
                {
                    "name": "hxSelectOob",
                    "doc": "Out-of-band swaps (comma-separated)",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "values"
                        }
                    ]
                },
                {
                    "name": "hxSwapOob",
                    "doc": "Marks element for out-of-band swap",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string|bool",
                            "name": "swap"
                        }
                    ]
                },
                {
                    "name": "hxSync",
                    "doc": "Synchronizes requests on this element",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "strategy"
                        }
                    ]
                },
                {
                    "name": "hxTarget",
                    "doc": "Target element for response",
                    "returns": "static",
                    "args": [
                        {
                            "type": "HxTarget|string",
                            "name": "target"
                        }
                    ]
                },
                {
                    "name": "hxTrigger",
                    "doc": "Specifies trigger events",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "spec"
                        }
                    ]
                },
                {
                    "name": "hxValidate",
                    "doc": "Validates before request (\"true\")",
                    "returns": "static",
                    "args": [
                        {
                            "type": "bool",
                            "name": "true"
                        }
                    ]
                },
                {
                    "name": "hxVals",
                    "doc": "Adds extra values to requests (JSON)",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "json"
                        }
                    ]
                },
                {
                    "name": "hxHeaders",
                    "doc": "Adds extra headers (JSON)",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "json"
                        }
                    ]
                },
                {
                    "name": "hxSwap",
                    "doc": "Swap strategy for response",
                    "returns": "static",
                    "args": [
                        {
                            "type": "HxSwap|string",
                            "name": "strategy"
                        }
                    ]
                },
                {
                    "name": "hxDisinherit",
                    "doc": "Prevents inheriting specified attributes",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "attrs"
                        }
                    ]
                },
                {
                    "name": "hxInherit",
                    "doc": "Forces inheritance of specified attributes",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "attrs"
                        }
                    ]
                },
                {
                    "name": "hxHistoryElt",
                    "doc": "Marks element as the one saved to history",
                    "returns": "static",
                    "args": []
                },
                {
                    "name": "hxDisabledElt",
                    "doc": "Adds the disabled attribute to the specified elements while a request is in flight",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "selector"
                        }
                    ]
                },
                {
                    "name": "hxOn",
                    "doc": "Handles any event with inline script (uses hx-on:* syntax)",
                    "returns": "static",
                    "args": [
                        {
                            "type": "string",
                            "name": "event"
                        },
                        {
                            "type": "string",
                            "name": "js"
                        }
                    ]
                }
            ]
        }
    ]
}
```

## License

MIT
