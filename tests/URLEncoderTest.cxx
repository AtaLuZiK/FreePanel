#include <cstddef>
#include <cctype>
#include <URLEncoder.h>
#include <gtest/gtest.h>

TEST(URLEncoderTest, SimpleString)
{
    const char *origin = "This is a simple string";
    size_t needSize = strlen(origin);
    char *encoded = (char *)malloc(needSize * 3 + 1);
    char *decoded = (char *)malloc(needSize + 1);
    URLEncoder::Encode(origin, encoded);
    URLEncoder::Decode(encoded, decoded);
    ASSERT_STREQ(origin, decoded);
    free(encoded);
    free(decoded);
}


TEST(URLEncoderTest, SymbolTest)
{
    const char *origin = "~!@#$%^&*()_+-='\";[]{},./<>?";
    const char *preEncoded = "%7e%21%40%23%24%25%5e%26%2a%28%29_%2b-%3d%27%22%3b%5b%5d%7b%7d%2c.%2f%3c%3e%3f";
    size_t needSize = strlen(origin) * 3;
    char *encoded = (char *)malloc(needSize + 1);
    char *decoded = (char *)malloc(needSize + 1);
    URLEncoder::Encode(origin, encoded);
    ASSERT_STREQ(preEncoded, encoded);
    URLEncoder::Decode(encoded, decoded);
    ASSERT_STREQ(origin, decoded);
    free(encoded);
    free(decoded);
}

TEST(URLEncoderTest, MultiBytesCharTest)
{
    const char *origin = "这是1段中文URL编码测试";
    const char *preEncoded = "%e8%bf%99%e6%98%af1%e6%ae%b5%e4%b8%ad%e6%96%87URL%e7%bc%96%e7%a0%81%e6%b5%8b%e8%af%95";
    size_t needSize = strlen(origin);
    char *encoded = (char *)malloc(needSize * 3 + 1);
    char *decoded = (char *)malloc(needSize + 1);
    URLEncoder::Encode(origin, encoded);
    ASSERT_STREQ(preEncoded, encoded);
    URLEncoder::Decode(encoded, decoded);
    ASSERT_STREQ(origin, decoded);
    free(encoded);
    free(decoded);
}

int main(int argc, char *argv[])
{
    testing::InitGoogleTest(&argc, argv);
    return RUN_ALL_TESTS();
}

